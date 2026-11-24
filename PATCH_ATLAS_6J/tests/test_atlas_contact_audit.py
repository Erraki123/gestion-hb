import importlib.util
from pathlib import Path
import unittest

src = Path(__file__).resolve().parents[1] / 'tools' / 'atlas_contact_audit.py'
spec = importlib.util.spec_from_file_location('atlas_contact_audit', src)
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)

class TestAtlasContactAudit(unittest.TestCase):
    def test_case_accent_normalization(self):
        self.assertTrue(mod.reasonable_match('Clinique Maâmoura', 'Kenitra', 'Clinique Maamoura', 'Kenitra, Morocco'))
    def test_reject_wrong_city(self):
        self.assertFalse(mod.reasonable_match('Clinique Maâmoura', 'Kenitra', 'Clinique Maamoura', 'Fes, Morocco'))
    def test_reject_other_company(self):
        self.assertFalse(mod.reasonable_match('Clinique Maâmoura', 'Kenitra', 'Hotel Casablanca', 'Kenitra, Morocco'))
    def test_audit_does_not_invent(self):
        self.assertEqual(mod.audit([{'company':'X','category':'client_final','phone':'','email':'','website':''}])['missing_contact']['phone'], 1)

if __name__ == '__main__': unittest.main()
