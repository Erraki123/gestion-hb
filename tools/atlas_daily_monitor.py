#!/usr/bin/env python3
"""HN BLOOM ATLAS: scheduled, offline review of locally cached datasets.

No network, no CRM DB access, no outbound messages. This script runs the
existing phase 6F/6K/6P scripts and records operational status.
"""
import argparse
import json
import os
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path


def utc_now():
    return datetime.now(timezone.utc).isoformat()


def atomic_json(path, obj):
    tmp = path.with_name(path.name + '.tmp')
    with tmp.open('w', encoding='utf-8') as f:
        json.dump(obj, f, indent=2, ensure_ascii=False)
    os.replace(tmp, path)


def locked(lock_path):
    """OS-level nonblocking exclusive lock; released even after process exits."""
    lock_path.parent.mkdir(parents=True, exist_ok=True)
    f = lock_path.open('a+b')
    try:
        f.seek(0)
        if f.read(1) == b'':
            f.seek(0)
            f.write(b'0')
            f.flush()
        f.seek(0)
        if os.name == 'nt':
            import msvcrt
            msvcrt.locking(f.fileno(), msvcrt.LK_NBLCK, 1)
        else:
            import fcntl
            fcntl.flock(f.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        return f
    except (OSError, IOError):
        f.close()
        return None


def run_pipeline(project, python=sys.executable):
    project = Path(project).resolve()
    folder = project / 'storage' / 'app' / 'prospect-imports'
    logs = project / 'storage' / 'app' / 'atlas-local-logs'
    folder.mkdir(parents=True, exist_ok=True)
    logs.mkdir(parents=True, exist_ok=True)
    source = folder / 'atlas-osm-maroc.csv'
    if not (project / 'artisan').exists():
        raise RuntimeError('Laravel project root missing artisan')
    if not source.is_file():
        raise RuntimeError('Missing atlas-osm-maroc.csv; run Phase 6E first')
    script_smart = project / 'tools' / 'atlas_smart_select.py'
    script_merge = project / 'tools' / 'atlas_contact_merge.py'
    script_digest = project / 'tools' / 'atlas_local_digest.py'
    for s in (script_smart, script_merge, script_digest):
        if not s.is_file():
            raise RuntimeError('Missing prerequisite: ' + s.name)
    lock = locked(folder / '.atlas-6q.lock')
    if lock is None:
        return {'status':'already_running','note':'Concurrent execution skipped safely'}
    report = folder / 'atlas-daily-status.json'
    log_file = logs / ('atlas-' + datetime.now(timezone.utc).strftime('%Y%m%d-%H%M%S-%f') + '.log')
    output = {'started_at_utc':utc_now(), 'status':'running', 'mode':'offline',
              'live_discovery':False, 'db_writes':0, 'outbound_messages':0,
              'source_mtime_utc':datetime.fromtimestamp(source.stat().st_mtime, timezone.utc).isoformat(),
              'log':str(log_file.relative_to(project)).replace('\\','/')}
    commands = [[python,str(script_smart),'--source',str(source),'--output-dir',str(folder),'--limit','60']]
    evidence = folder / 'atlas-osm-pbf-evidence.csv'
    if evidence.is_file():
        commands.append([python,str(script_merge),'--candidates',str(folder/'atlas-smart-preselection.csv'),
                         '--evidence',str(evidence),'--output',str(folder/'atlas-contact-review.csv')])
        output['contact_evidence_used'] = True
    else:
        output['contact_evidence_used'] = False
        # Do not use stale prior contact report when evidence file is missing.
    commands.append([python,str(script_digest),'--candidates',str(folder/'atlas-smart-preselection.csv'),
                     '--contacts',str(folder/'atlas-contact-review.csv' if evidence.is_file() else folder/'__no_contacts.csv'),
                     '--state',str(folder/'atlas-local-monitor-state.json'),
                     '--report',str(folder/'atlas-local-digest.json')])
    try:
        with log_file.open('w', encoding='utf-8') as log:
            for command in commands:
                log.write('RUN ' + Path(command[1]).name + '\n')
                log.flush()
                completed = subprocess.run(command, cwd=project, stdout=log, stderr=subprocess.STDOUT, check=False)
                if completed.returncode != 0:
                    raise RuntimeError(Path(command[1]).name + ' failed, exit code ' + str(completed.returncode))
        digest = json.loads((folder / 'atlas-local-digest.json').read_text(encoding='utf-8'))
        output.update({'status':'ok', 'candidates':digest['candidates'],
                       'new_since_last_run':digest['new_since_last_run'],
                       'changed_since_last_run':digest['changed_since_last_run'],
                       'contacts_by_status':digest.get('contacts_by_status',{}),
                       'warning':'CSV monitoring only; sources are not updated from Internet.'})
    except Exception as e:
        output.update({'status':'failed', 'error':str(e)})
    finally:
        output['finished_at_utc'] = utc_now()
        atomic_json(report,output)
        lock.close()
    return output


def main():
    parser = argparse.ArgumentParser(description='ATLAS local monitoring; no network or CRM writes')
    parser.add_argument('--project',type=Path,default=Path.cwd())
    args = parser.parse_args()
    try:
        result = run_pipeline(args.project)
    except Exception as e:
        result = {'status':'failed', 'error':str(e)}
    print(json.dumps(result,ensure_ascii=False,indent=2))
    return 0 if result['status'] in ('ok','already_running') else 1

if __name__=='__main__':
    raise SystemExit(main())
