import importlib.util, pathlib, unittest, tempfile, csv
MOD=pathlib.Path(__file__).resolve().parents[1]/'tools'/'atlas_smart_score.py'
spec=importlib.util.spec_from_file_location('atlas_smart_score',MOD)
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
class TestSmartScore(unittest.TestCase):
 def test_hotel_not_health(self):
  self.assertEqual(m.sector_of('Hôtel Kennedy Hospitality Resort')[0],'hotellerie')
 def test_technical_lab_unknown(self):
  self.assertEqual(m.sector_of('Le Laboratoire Conception et Systèmes')[0],'non_determine')
 def test_contact_unverified(self):
  x=m.calculate(dict(company='Clinique Sebou',city='Kenitra',source_url='https://www.openstreetmap.org/node/123'),dict(contact_status='review_required',phone='+212537360331'))
  self.assertLessEqual(x['data_confidence'],65)
  self.assertEqual(x['contact_status'],'review_required')
  self.assertIn('non confirme',x['reason'])
 def test_multiple_locations_penalty(self):
  a=m.calculate(dict(company='Hotel Central',city='Fes',points_meme_nom_ville='1'))
  b=m.calculate(dict(company='Hotel Central',city='Fes',points_meme_nom_ville='3'))
  self.assertLess(b['opportunity_priority'],a['opportunity_priority'])
 def test_output_without_network(self):
  with tempfile.TemporaryDirectory() as d:
   d=pathlib.Path(d); src=d/'src.csv'
   with src.open('w',newline='',encoding='utf-8') as f:
    w=csv.DictWriter(f,fieldnames=['company','city']);w.writeheader();w.writerow({'company':'Clinique Sebou','city':'Kenitra'})
   report=m.run(src,d/'missing.csv',d/'out.csv',d/'out.json')
   self.assertEqual(report['source_records'],1);self.assertEqual(report['db_writes'],0)
   self.assertTrue((d/'out.csv').exists())
if __name__=='__main__':unittest.main()
