#!/usr/bin/env python3
"""Explicitly requested Geofabrik PBF downloader with visible progress; never automatically invoked by installer."""
import argparse
import os
from pathlib import Path
import sys
import urllib.request
from urllib.parse import urlsplit

URL='https://download.geofabrik.de/africa/morocco-latest.osm.pbf'
ALLOWED={'download.geofabrik.de','download-ext1.geofabrik.de','download-ext2.geofabrik.de'}

def main():
    p=argparse.ArgumentParser()
    p.add_argument('--destination',required=True,type=Path)
    args=p.parse_args()
    dst=args.destination
    if dst.exists():
        print('PBF already exists:', dst);return
    dst.parent.mkdir(parents=True,exist_ok=True)
    tmp=dst.with_name(dst.name+'.part')
    req=urllib.request.Request(URL,headers={'User-Agent':'HNBloomATLAS/1.0 (local OSM research)'})
    try:
        with urllib.request.urlopen(req,timeout=120) as response:
            if urlsplit(response.url).hostname not in ALLOWED:
                raise ValueError('Unexpected redirect; refused')
            size=int(response.headers.get('Content-Length') or '0')
            count=0
            with tmp.open('wb') as f:
                while True:
                    block=response.read(2*1024*1024)
                    if not block:break
                    f.write(block);count+=len(block)
                    if count%(10*1024*1024)<2*1024*1024:
                        print('Downloaded {:.1f} MB{}'.format(count/1048576, ' / {:.1f} MB'.format(size/1048576) if size else ''), flush=True)
        if count < 1024*1024:raise ValueError('PBF download unexpectedly small')
        if size and size != count:raise ValueError('PBF download truncated')
        os.replace(tmp,dst)
        print('Download complete:',dst, '({:.1f} MB)'.format(count/1048576))
    except Exception:
        if tmp.exists():tmp.unlink()
        raise

if __name__=='__main__':
    try:main()
    except (OSError,ValueError) as ex:
        print('DOWNLOAD ERROR:',ex,file=sys.stderr)
        sys.exit(1)
