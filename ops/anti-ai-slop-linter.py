# -*- coding: utf-8 -*-
"""
Shim for ops/anti_ai_slop_linter.py allowing execution via hyphenated filename:
python ops/anti-ai-slop-linter.py [options]
"""
import os
import sys
import runpy

target = os.path.join(os.path.dirname(__file__), 'anti_ai_slop_linter.py')
runpy.run_path(target, run_name='__main__')
