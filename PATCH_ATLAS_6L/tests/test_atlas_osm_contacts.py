import sys
import unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1] / 'tools'))
from atlas_osm_contacts import collect, valid_contact, read_evidence

def c(name='Clinique Test', city='Rabat', id='poi_123_34_22'):
    return {'company':name,'city':city,'category':'client_final','phone':'','email':'','website':'','source_url':'https://www.openstreetmap.org','id':id}
class ContactExtractionTests(unittest.TestCase):
    def test_matching_osm_id_and_name(self):
        rows,amb=collect([c()], [('node',123,{'name':'Clinique Test','contact:phone':'+212612345678','contact:email':'info@example.org','contact:website':'https://example.org'})])
        self.assertEqual(len(rows),1);self.assertEqual(amb,0)
        self.assertEqual(rows[0]['phone'],'+212612345678')
        self.assertEqual(rows[0]['evidence_url'],'https://www.openstreetmap.org/node/123')
    def test_other_company_not_accepted(self):
        rows,_=collect([c()], [('node',123,{'name':'Autre Clinique','phone':'+212612345678'})])
        self.assertEqual(rows,[])
    def test_bad_contacts_not_accepted(self):
        rows,_=collect([c()], [('node',123,{'name':'Clinique Test','email':'a@evil.com;other@a.com','website':'http://example.com','phone':'123'})])
        self.assertEqual(rows,[])
    def test_osm_type_collision_is_ambiguous(self):
        rows,amb=collect([c()], [('node',123,{'name':'Clinique Test','phone':'+212612345678'}),('way',123,{'name':'Clinique Test','phone':'+212612345678'})])
        self.assertEqual(rows,[]);self.assertEqual(amb,1)
    def test_missing_contact_not_fabricated(self):
        rows,_=collect([c()], [('node',123,{'name':'Clinique Test'})])
        self.assertEqual(rows,[])
    def test_bad_pbf_contact_url(self):
        self.assertFalse(valid_contact('website','http://test.org'))
        self.assertTrue(valid_contact('website','https://test.org'))
if __name__=='__main__':unittest.main()
