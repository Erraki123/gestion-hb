import importlib.util
import json
import tempfile
import unittest
from pathlib import Path

spec = importlib.util.spec_from_file_location('atlas_daily_monitor', Path(__file__).resolve().parents[1]/'tools'/'atlas_daily_monitor.py')
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)

class MonitorTests(unittest.TestCase):
    def test_missing_artisan(self):
        with tempfile.TemporaryDirectory() as t:
            with self.assertRaisesRegex(RuntimeError,'artisan'):
                mod.run_pipeline(t)
    def test_missing_csv(self):
        with tempfile.TemporaryDirectory() as t:
            (Path(t)/'artisan').write_text('',encoding='utf8')
            with self.assertRaisesRegex(RuntimeError,'atlas-osm-maroc.csv'):
                mod.run_pipeline(t)
    def test_lock_exclusivity(self):
        with tempfile.TemporaryDirectory() as t:
            p=Path(t)/'lock'
            first=mod.locked(p)
            self.assertIsNotNone(first)
            try:
                second=mod.locked(p)
                self.assertIsNone(second)
            finally:
                first.close()
            third=mod.locked(p)
            self.assertIsNotNone(third)
            third.close()

if __name__=='__main__': unittest.main()
