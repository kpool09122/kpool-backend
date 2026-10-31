"""Read the committed, nonsecret production environment without shell execution."""
import re
import shlex

from contract import runtime_environment


def load_production_environment(path):
    values = {}
    for number, line in enumerate(path.read_text().splitlines(), 1):
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        match = re.fullmatch(r'([A-Z][A-Z0-9_]*)\s*=(.*)', line)
        if not match:
            raise ValueError(f'Invalid production environment assignment at line {number}')
        key, raw = match.groups()
        if key in values:
            raise ValueError('Duplicate production environment key: ' + key)
        try:
            tokens = shlex.split(raw, comments=True)
        except ValueError:
            raise ValueError(f'Invalid production environment quoting at line {number}') from None
        if len(tokens) > 1:
            raise ValueError('Quote whitespace in production environment key: ' + key)
        value = tokens[0] if tokens else ''
        if '<' in value or '>' in value or '${' in value:
            raise ValueError('Unresolved production environment value: ' + key)
        values[key] = value
    runtime_environment(values)
    return values
