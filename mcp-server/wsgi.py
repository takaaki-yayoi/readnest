"""
WSGI entry point for ReadNest MCP Server
"""

from app import app

# For gunicorn
application = app
