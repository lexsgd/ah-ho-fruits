# Ah Ho Fruit QBO MCP

This is a standalone stdio MCP server for answering month-end bookkeeping questions from Ah Ho Fruit's live QuickBooks Online data.

## Read-only guarantee

The server only offers QBO query and report reads. It never writes to QuickBooks, and the existing `QBO.post` method is neither exposed nor wired into the read-only facade or any MCP tool.

## Tools

- `run_query(query)` runs an arbitrary QBO `SELECT` query. Non-`SELECT` input is rejected.
- `report(name, params)` fetches a QBO report, such as `ProfitAndLoss` with `start_date` and `end_date`.
- `list_unpaid_invoices(limit)` lists invoices with money still owed.
- `invoice_detail(doc_number)` returns the complete QBO record for one invoice number.

## Install and run

Python 3.12 or newer and [uv](https://docs.astral.sh/uv/) are required.

```sh
uv sync
uv run qbo_mcp_server.py
```

The server uses stdio, so it is normally launched by an MCP client rather than used directly in a terminal.

## Configure Claude Code

Replace `<repo>` with this repository's absolute path:

```sh
claude mcp add --transport stdio ah-ho-qbo -- uv run --directory <repo> qbo_mcp_server.py
```

Equivalent MCP configuration:

```json
{
  "mcpServers": {
    "ah-ho-qbo": {
      "type": "stdio",
      "command": "uv",
      "args": ["run", "--directory", "<repo>", "qbo_mcp_server.py"]
    }
  }
}
```

## Credentials

By default the server reads `<repo>/.env`. It requires:

```dotenv
QBO_B2C_REALM_ID=...
QBO_B2C_CLIENT_ID=...
QBO_B2C_CLIENT_SECRET=...
QBO_B2C_REFRESH_TOKEN=...
```

Set `AHHO_ENV_PATH` to use a different env file:

```sh
AHHO_ENV_PATH=/absolute/path/to/.env uv run qbo_mcp_server.py
```

## Token-rotation note

This server shares `QBO_B2C_REFRESH_TOKEN` in `.env` with the existing sync. Both reuse the existing atomic `set_env_value` token update and read `.env` fresh at startup, so rotation is safe for normal interactive, month-end use. The residual race is both processes refreshing in the same second. The clean long-term fix, outside this server's scope, is a separate Intuit app for the MCP.
