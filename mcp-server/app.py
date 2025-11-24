"""
ReadNest Remote MCP Server
FastAPI + SSE implementation
"""

import os
import json
import asyncio
from typing import Any, Dict, List, Optional
from fastapi import FastAPI, Request, HTTPException
from fastapi.responses import StreamingResponse
from fastapi.middleware.cors import CORSMiddleware
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv

load_dotenv()

app = FastAPI(title="ReadNest MCP Server", version="0.1.0")

# CORS設定
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # 本番環境では制限すべき
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# データベース接続設定
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME'),
    'charset': 'utf8mb4'
}


def get_db_connection():
    """データベース接続を取得"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        print(f"Database connection error: {e}")
        raise HTTPException(status_code=500, detail="Database connection failed")


def authenticate_api_key(api_key: str) -> Optional[int]:
    """API Keyを検証してuser_idを返す"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT user_id, expires_at FROM b_api_keys WHERE api_key = %s AND is_active = 1",
            (api_key,)
        )
        result = cursor.fetchone()

        if not result:
            return None

        # 有効期限チェック
        if result['expires_at'] and result['expires_at'] < datetime.now():
            return None

        return result['user_id']

    finally:
        cursor.close()
        conn.close()


async def handle_mcp_message(message: Dict[str, Any], user_id: int) -> Dict[str, Any]:
    """MCPメッセージを処理"""
    method = message.get('method', '')
    params = message.get('params', {})
    msg_id = message.get('id')

    if method == 'initialize':
        return {
            'jsonrpc': '2.0',
            'id': msg_id,
            'result': {
                'protocolVersion': '2024-11-05',
                'serverInfo': {
                    'name': 'readnest-mcp',
                    'version': '0.1.0'
                },
                'capabilities': {
                    'tools': {}
                }
            }
        }

    elif method == 'tools/list':
        return {
            'jsonrpc': '2.0',
            'id': msg_id,
            'result': {
                'tools': [
                    {
                        'name': 'get_bookshelf',
                        'description': '本棚のデータを取得します',
                        'inputSchema': {
                            'type': 'object',
                            'properties': {
                                'status': {
                                    'type': 'string',
                                    'enum': ['tsundoku', 'reading', 'finished', 'read'],
                                    'description': '本のステータス'
                                },
                                'limit': {
                                    'type': 'integer',
                                    'description': '取得件数',
                                    'default': 100
                                },
                                'offset': {
                                    'type': 'integer',
                                    'description': 'オフセット',
                                    'default': 0
                                }
                            }
                        }
                    },
                    {
                        'name': 'get_reading_stats',
                        'description': '読書統計情報を取得します',
                        'inputSchema': {
                            'type': 'object',
                            'properties': {}
                        }
                    }
                ]
            }
        }

    elif method == 'tools/call':
        tool_name = params.get('name', '')
        tool_args = params.get('arguments', {})

        if tool_name == 'get_bookshelf':
            return await handle_get_bookshelf(tool_args, user_id, msg_id)
        elif tool_name == 'get_reading_stats':
            return await handle_get_reading_stats(user_id, msg_id)
        else:
            return {
                'jsonrpc': '2.0',
                'id': msg_id,
                'error': {
                    'code': -32601,
                    'message': f'Unknown tool: {tool_name}'
                }
            }

    else:
        return {
            'jsonrpc': '2.0',
            'id': msg_id,
            'error': {
                'code': -32601,
                'message': f'Method not found: {method}'
            }
        }


async def handle_get_bookshelf(args: Dict[str, Any], user_id: int, msg_id: Any) -> Dict[str, Any]:
    """本棚データを取得"""
    status = args.get('status')
    limit = min(args.get('limit', 100), 1000)
    offset = args.get('offset', 0)

    status_map = {
        'tsundoku': 1,
        'reading': 2,
        'finished': 3,
        'read': 4
    }

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        # SQLクエリ構築
        sql = """
            SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
                   bl.image_url, bl.status, bl.rating, bl.total_page, bl.current_page,
                   bl.finished_date, bl.update_date,
                   COALESCE(bl.author, br.author, '') as author
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = %s
        """

        params = [user_id]

        if status and status in status_map:
            sql += " AND bl.status = %s"
            params.append(status_map[status])

        sql += " ORDER BY bl.update_date DESC LIMIT %s OFFSET %s"
        params.extend([limit, offset])

        cursor.execute(sql, params)
        results = cursor.fetchall()

        # フォーマット
        status_name = {
            1: '積読',
            2: '読書中',
            3: '読了',
            4: '既読'
        }

        output_lines = []
        for book in results:
            line = f"📖 {book['name']}"
            if book['author']:
                line += f" / {book['author']}"
            line += f" ({status_name[book['status']]})"

            if book['rating']:
                line += f" ⭐️ {book['rating']}"

            if book['current_page'] and book['total_page']:
                progress = int((book['current_page'] / book['total_page']) * 100)
                line += f" | {book['current_page']}/{book['total_page']}ページ ({progress}%)"

            output_lines.append(line)

        text = "\n".join(output_lines) if output_lines else "該当する本が見つかりませんでした"

        return {
            'jsonrpc': '2.0',
            'id': msg_id,
            'result': {
                'content': [
                    {
                        'type': 'text',
                        'text': text
                    }
                ]
            }
        }

    finally:
        cursor.close()
        conn.close()


async def handle_get_reading_stats(user_id: int, msg_id: Any) -> Dict[str, Any]:
    """読書統計を取得"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        # 総書籍数
        cursor.execute("SELECT COUNT(*) as total FROM b_book_list WHERE user_id = %s", (user_id,))
        total_books = cursor.fetchone()['total']

        # ステータス別
        cursor.execute("""
            SELECT status, COUNT(*) as count
            FROM b_book_list
            WHERE user_id = %s
            GROUP BY status
        """, (user_id,))

        by_status = {
            'tsundoku': 0,
            'reading': 0,
            'finished': 0,
            'read': 0
        }

        status_map = {1: 'tsundoku', 2: 'reading', 3: 'finished', 4: 'read'}
        for row in cursor.fetchall():
            key = status_map.get(row['status'])
            if key:
                by_status[key] = row['count']

        # 今年の実績
        cursor.execute("""
            SELECT COUNT(*) as count, SUM(total_page) as pages
            FROM b_book_list
            WHERE user_id = %s AND status = 3
            AND YEAR(finished_date) = YEAR(NOW())
        """, (user_id,))

        this_year = cursor.fetchone()

        # 出力
        output_lines = [
            "📊 読書統計\n",
            f"総書籍数: {total_books}冊",
            f"  - 積読: {by_status['tsundoku']}冊",
            f"  - 読書中: {by_status['reading']}冊",
            f"  - 読了: {by_status['finished']}冊",
            f"  - 既読: {by_status['read']}冊",
            "",
            "今年の実績:",
            f"  - 読了: {this_year['count']}冊",
            f"  - ページ数: {this_year['pages']:,}ページ" if this_year['pages'] else "  - ページ数: 0ページ"
        ]

        return {
            'jsonrpc': '2.0',
            'id': msg_id,
            'result': {
                'content': [
                    {
                        'type': 'text',
                        'text': '\n'.join(output_lines)
                    }
                ]
            }
        }

    finally:
        cursor.close()
        conn.close()


@app.get("/")
async def root():
    """ヘルスチェック"""
    return {"status": "ok", "service": "ReadNest MCP Server", "version": "0.1.0"}


@app.post("/messages")
async def handle_message(request: Request):
    """MCPメッセージを受信して処理"""
    # Authorization ヘッダーからAPI Keyを取得
    auth_header = request.headers.get('Authorization', '')
    if not auth_header.startswith('Bearer '):
        raise HTTPException(status_code=401, detail="Invalid authorization header")

    api_key = auth_header[7:]
    user_id = authenticate_api_key(api_key)

    if not user_id:
        raise HTTPException(status_code=401, detail="Invalid API key")

    # メッセージを取得
    message = await request.json()

    # メッセージを処理
    response = await handle_mcp_message(message, user_id)

    return response


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
