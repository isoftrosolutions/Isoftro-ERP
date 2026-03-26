import re
import os

def get_db_schema(sql_file, tables):
    schema = {}
    if not os.path.exists(sql_file):
        return f"File {sql_file} not found"
    
    with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        for table in tables:
            # Simple regex to find CREATE TABLE for a specific table
            pattern = rf"CREATE TABLE [`']?{table}[`']?\s*\((.*?)\);"
            match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
            if match:
                print(f"--- TABLE: {table} ---")
                print(match.group(1).strip())
                print("-" * 40)
            else:
                print(f"Table {table} not found")

if __name__ == "__main__":
    base_path = "c:\\Apache24\\htdocs\\erp"
    tables_to_check = ['acc_vouchers', 'acc_ledger_postings', 'acc_accounts', 'payment_transactions', 'expenses']
    get_db_schema(os.path.join(base_path, 'database', 'realdb.sql'), tables_to_check)
