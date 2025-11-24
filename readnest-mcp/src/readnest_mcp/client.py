"""ReadNest API Client"""

import os
from typing import Optional, Dict, Any, List
import httpx
from dotenv import load_dotenv

load_dotenv()


class ReadNestClient:
    """ReadNest API Client

    ReadNest APIと通信してデータを取得するクライアント
    """

    def __init__(self):
        self.api_key = os.getenv("READNEST_API_KEY")
        self.base_url = os.getenv("READNEST_API_BASE_URL", "https://readnest.jp")

        if not self.api_key:
            raise ValueError("READNEST_API_KEY environment variable is required")

        self.headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Accept": "application/json"
        }

    async def get_bookshelf(
        self,
        status: Optional[str] = None,
        limit: int = 100,
        offset: int = 0
    ) -> Dict[str, Any]:
        """本棚データを取得

        Args:
            status: 本のステータス (tsundoku, reading, finished, read)
            limit: 取得件数
            offset: オフセット

        Returns:
            API レスポンス
        """
        params = {
            "limit": limit,
            "offset": offset
        }

        # ステータスをAPIの数値に変換
        status_map = {
            "tsundoku": 1,
            "reading": 2,
            "finished": 3,
            "read": 4
        }

        if status and status in status_map:
            params["status"] = status_map[status]

        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/mcp/bookshelf.php",
                headers=self.headers,
                params=params,
                timeout=10.0
            )
            response.raise_for_status()
            return response.json()

    async def get_stats(self) -> Dict[str, Any]:
        """読書統計情報を取得

        Returns:
            API レスポンス
        """
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/mcp/stats.php",
                headers=self.headers,
                timeout=10.0
            )
            response.raise_for_status()
            return response.json()
