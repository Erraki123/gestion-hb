import unittest
import sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'tools'))
from atlas_contact_merge import match,normalize,phone_ok,source_url_ok

C={'company':'Clinique Maâmoura','city':'Kenitra','category':'client_final','phone':'','email':'','website':'','source_url':'https://www.openstreetmap.org/','id':'a'}

def e(**kwargs):
    d={'company':'Clinique Maamoura','city':'Kénitra','phone':'+212 500000000','email':'contact@example.org','website':'https://example.org','evidence_url':'https://example.org/contact'}
    return dict(d,**kwargs)

class Tests(unittest.TestCase):
    def test_accent_matches_requires_review(self):
        rows,count=match([C],[e()]); self.assertEqual(count['review_required'],1)
        self.assertEqual(rows[0]['email'],'contact@example.org')
        self.assertEqual(rows[0]['contact_evidence_url'],'https://example.org/contact')
    def test_company_mismatch_not_merged(self):
        rows,count=match([C],[e(company='Different Company')]);self.assertEqual(count['missing'],1);self.assertFalse(rows[0]['email'])
    def test_ambiguous_same_name_city(self):
        rows,count=match([C,dict(C,id='b')],[e()]);self.assertEqual(count['ambiguous'],2)
    def test_reject_untrusted_source(self):
        rows,count=match([C],[e(evidence_url='http://example.org/contact')]);self.assertEqual(count['rejected'],1)
    def test_reject_invalid_contact(self):
        rows,count=match([C],[e(phone='123')]);self.assertEqual(count['rejected'],1)
    def test_existing_field_preserved(self):
        rows,count=match([dict(C,phone='0611111111')],[e()]);self.assertEqual(rows[0]['phone'],'0611111111')
    def test_source_validation(self):
        self.assertFalse(source_url_ok('javascript:alert(1)'));self.assertTrue(phone_ok('+212 522-123456'))

if __name__=='__main__':unittest.main()
