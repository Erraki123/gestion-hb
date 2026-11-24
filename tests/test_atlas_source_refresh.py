import importlib.util
import json
import tempfile
import unittest
from pathlib import Path
from email.message import Message

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('atlas_source_refresh',ROOT/'tools'/'atlas_source_refresh.py')
a = importlib.util.module_from_spec(spec)
spec.loader.exec_module(a)

class FakeResponse:
    def __init__(self, url):
        self.url=url
        self.headers=Message()
        self.headers['Content-Length']='493702122'
        self.headers['Last-Modified']='Sun, 15 Nov 2026 08:32:00 GMT'
        self.headers['ETag']='"a"'
    def geturl(self):return self.url
    def __enter__(self):return self
    def __exit__(self,*args):return False

class Tests(unittest.TestCase):
    def test_http_metadata(self):
        x=a.inspect_remote(lambda request,timeout:FakeResponse(a.URL))
        self.assertEqual(x['content_length'],493702122)
        self.assertTrue(x['last_modified_utc'].startswith('2026-11-15'))
    def test_redirect_rejected(self):
        with self.assertRaises(ValueError):a.inspect_remote(lambda request,timeout:FakeResponse('https://example.com/file.zip'))
    def test_check_is_metadata_only(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d);(p/'artisan').touch()
            r={'etag':'"a"','content_length':42,'last_modified_utc':'2026-11-15T00:00:00+00:00'}
            result=a.status(p,r)
            self.assertIsNone(result['source_updated_since_previous_check'])
            self.assertFalse((p/'storage/app/prospect-imports/morocco-latest-free.shp.zip').exists())
            result=a.status(p,r)
            self.assertFalse(result['source_updated_since_previous_check'])
            r['etag']='"b"'
            self.assertTrue(a.status(p,r)['source_updated_since_previous_check'])
    def test_local_stage_not_automatically_created(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d);(p/'artisan').touch()
            a.status(p,{'etag':None,'last_modified_utc':None,'content_length':None})
            self.assertFalse((p/'storage/app/prospect-imports/atlas-refresh-preview').exists())

if __name__=='__main__':unittest.main()
