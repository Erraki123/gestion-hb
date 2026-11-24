import csv
import importlib.util
import tempfile
import unittest
from pathlib import Path

spec=importlib.util.spec_from_file_location('atlas_local_digest',Path(__file__).resolve().parents[1]/'tools'/'atlas_local_digest.py')
mod=importlib.util.module_from_spec(spec);spec.loader.exec_module(mod)

class DigestTests(unittest.TestCase):
    def test_first_then_unchanged_then_new(self):
        with tempfile.TemporaryDirectory() as temp:
            folder=Path(temp); src=folder/'c.csv'; contacts=folder/'none.csv'; state=folder/'state.json';report=folder/'report.json'
            def write(rows):
                with src.open('w',newline='',encoding='utf-8') as f:
                    w=csv.DictWriter(f,fieldnames=['id','company','city']);w.writeheader();w.writerows(rows)
            write([{'id':'osm1','company':'Clinic A','city':'Rabat'}])
            a=mod.run(src,contacts,state,report)
            self.assertTrue(a['first_run']);self.assertEqual(a['new_since_last_run'],1)
            b=mod.run(src,contacts,state,report)
            self.assertEqual(b['new_since_last_run'],0);self.assertEqual(b['changed_since_last_run'],0)
            write([{'id':'osm1','company':'Clinic A','city':'Rabat'},{'id':'osm2','company':'Hotel B','city':'Fes'}])
            c=mod.run(src,contacts,state,report)
            self.assertEqual(c['new_since_last_run'],1)
    def test_contact_status_count(self):
        with tempfile.TemporaryDirectory() as temp:
            d=Path(temp);p=d/'c.csv';e=d/'e.csv'
            p.write_text('company,city\nA,Rabat\n',encoding='utf-8')
            e.write_text('contact_status\nmissing\nreview_required\n',encoding='utf-8')
            o=mod.run(p,e,d/'state.json',d/'report.json')
            self.assertEqual(o['contacts_by_status']['review_required'],1)
if __name__=='__main__':unittest.main()
