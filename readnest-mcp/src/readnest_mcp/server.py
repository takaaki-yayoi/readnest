"""ReadNest MCP Server

MCPサーバーのメイン実装
"""

import asyncio
import logging
from typing import Any, Optional
from mcp.server import Server
from mcp.server.stdio import stdio_server
from mcp.types import Tool, TextContent

from .client import ReadNestClient

# ロギング設定
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# MCPサーバーインスタンス
app = Server("readnest")

# ReadNestクライアント
client = ReadNestClient()


@app.list_tools()
async def list_tools() -> list[Tool]:
    """利用可能なツールのリストを返す"""
    return [
        Tool(
            name="get_bookshelf",
            description="""本棚のデータを取得します。

パラメータ:
- status (optional): 本のステータス
  - tsundoku: 積読
  - reading: 読書中
  - finished: 読了
  - read: 既読
- limit (optional): 取得件数 (デフォルト: 100)
- offset (optional): オフセット (デフォルト: 0)

例:
- 「読了した本を10冊教えて」→ status=finished, limit=10
- 「積読リストを見せて」→ status=tsundoku
""",
            inputSchema={
                "type": "object",
                "properties": {
                    "status": {
                        "type": "string",
                        "enum": ["tsundoku", "reading", "finished", "read"],
                        "description": "本のステータス"
                    },
                    "limit": {
                        "type": "integer",
                        "description": "取得件数",
                        "default": 100,
                        "minimum": 1,
                        "maximum": 1000
                    },
                    "offset": {
                        "type": "integer",
                        "description": "オフセット",
                        "default": 0,
                        "minimum": 0
                    }
                }
            }
        ),
        Tool(
            name="get_reading_stats",
            description="""読書統計情報を取得します。

取得できる情報:
- 総書籍数
- ステータス別の冊数
- 今年の読了冊数とページ数
- 今月の読了冊数とページ数
- 平均評価
- 読了した総ページ数

例:
- 「今年は何冊読んだ?」
- 「読書統計を教えて」
- 「積読は何冊ある?」
""",
            inputSchema={
                "type": "object",
                "properties": {}
            }
        )
    ]


@app.call_tool()
async def call_tool(name: str, arguments: Any) -> list[TextContent]:
    """ツールを実行"""
    try:
        if name == "get_bookshelf":
            # 本棚データを取得
            status = arguments.get("status")
            limit = arguments.get("limit", 100)
            offset = arguments.get("offset", 0)

            result = await client.get_bookshelf(
                status=status,
                limit=limit,
                offset=offset
            )

            if not result.get("success"):
                return [TextContent(
                    type="text",
                    text=f"エラー: {result.get('error', 'Unknown error')}"
                )]

            books = result.get("data", [])
            total = result.get("total", 0)

            if not books:
                status_text = {
                    "tsundoku": "積読",
                    "reading": "読書中",
                    "finished": "読了",
                    "read": "既読"
                }.get(status, "")

                return [TextContent(
                    type="text",
                    text=f"{status_text}の本は見つかりませんでした。"
                )]

            # 本のリストをフォーマット
            status_name = {
                1: "積読",
                2: "読書中",
                3: "読了",
                4: "既読"
            }

            output_lines = [f"本棚データ (全{total}冊中{len(books)}冊を表示):\n"]

            for book in books:
                title = book.get("title", "タイトル不明")
                author = book.get("author", "著者不明")
                status_num = book.get("status", 0)
                rating = book.get("rating")
                current_page = book.get("current_page")
                total_page = book.get("total_page")

                line = f"📖 {title}"
                if author:
                    line += f" / {author}"

                line += f" ({status_name.get(status_num, '不明')})"

                if rating:
                    line += f" ⭐️ {rating}"

                if current_page and total_page:
                    progress = int((current_page / total_page) * 100)
                    line += f" | {current_page}/{total_page}ページ ({progress}%)"

                output_lines.append(line)

            return [TextContent(
                type="text",
                text="\n".join(output_lines)
            )]

        elif name == "get_reading_stats":
            # 統計情報を取得
            result = await client.get_stats()

            if not result.get("success"):
                return [TextContent(
                    type="text",
                    text=f"エラー: {result.get('error', 'Unknown error')}"
                )]

            data = result.get("data", {})

            # 統計情報をフォーマット
            output_lines = ["📊 読書統計\n"]

            # 総書籍数
            total = data.get("total_books", 0)
            output_lines.append(f"総書籍数: {total}冊")

            # ステータス別
            by_status = data.get("by_status", {})
            output_lines.append(f"  - 積読: {by_status.get('tsundoku', 0)}冊")
            output_lines.append(f"  - 読書中: {by_status.get('reading', 0)}冊")
            output_lines.append(f"  - 読了: {by_status.get('finished', 0)}冊")
            output_lines.append(f"  - 既読: {by_status.get('read', 0)}冊")

            # 今年の実績
            this_year = data.get("this_year", {})
            output_lines.append(f"\n今年の実績:")
            output_lines.append(f"  - 読了: {this_year.get('finished', 0)}冊")
            output_lines.append(f"  - ページ数: {this_year.get('pages', 0):,}ページ")

            # 今月の実績
            this_month = data.get("this_month", {})
            output_lines.append(f"\n今月の実績:")
            output_lines.append(f"  - 読了: {this_month.get('finished', 0)}冊")
            output_lines.append(f"  - ページ数: {this_month.get('pages', 0):,}ページ")

            # 平均評価
            avg_rating = data.get("average_rating")
            if avg_rating:
                output_lines.append(f"\n平均評価: ⭐️ {avg_rating}")

            # 総ページ数
            total_pages = data.get("total_pages_read", 0)
            output_lines.append(f"読了総ページ数: {total_pages:,}ページ")

            return [TextContent(
                type="text",
                text="\n".join(output_lines)
            )]

        else:
            return [TextContent(
                type="text",
                text=f"Unknown tool: {name}"
            )]

    except Exception as e:
        logger.error(f"Error in {name}: {e}", exc_info=True)
        return [TextContent(
            type="text",
            text=f"エラーが発生しました: {str(e)}"
        )]


async def main():
    """MCPサーバーを起動"""
    async with stdio_server() as (read_stream, write_stream):
        await app.run(
            read_stream,
            write_stream,
            app.create_initialization_options()
        )


if __name__ == "__main__":
    asyncio.run(main())
