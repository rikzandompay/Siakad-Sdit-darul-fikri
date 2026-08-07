#!/usr/bin/env python3
"""
Load testing script — untuk sistem sendiri, port 8001
Contoh: python3 loadtest.py --url http://127.0.0.1:8001 --concurrent 50 --requests 200
"""
import urllib.request
import urllib.error
import threading
import time
import sys
import ssl
import argparse

results = []
lock = threading.Lock()
start_time = 0

def worker(url, count):
    global results, start_time
    ctx = ssl._create_unverified_context()
    for i in range(count):
        t0 = time.perf_counter()
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "LoadTest/1.0"})
            resp = urllib.request.urlopen(req, timeout=10, context=ctx)
            status = resp.getcode()
            size = len(resp.read())
            elapsed = (time.perf_counter() - t0) * 1000
        except urllib.error.HTTPError as e:
            status = e.code
            size = 0
            elapsed = (time.perf_counter() - t0) * 1000
        except Exception as e:
            status = 0
            size = 0
            elapsed = (time.perf_counter() - t0) * 1000

        with lock:
            results.append((status, elapsed, size))

def print_progress(done, total):
    pct = done / total * 100
    bar = "█" * int(pct // 5) + "░" * (20 - int(pct // 5))
    elapsed = time.perf_counter() - start_time
    rps = done / elapsed if elapsed > 0 else 0
    sys.stdout.write(f"\r  [{bar}] {done}/{total} — {rps:.0f} req/s")
    sys.stdout.flush()

def main():
    global start_time
    parser = argparse.ArgumentParser(description="Load test untuk Sinta (localhost only)")
    parser.add_argument("--url", default="http://127.0.0.1:8001", help="Target URL")
    parser.add_argument("--concurrent", type=int, default=20, help="Concurrent threads")
    parser.add_argument("--requests", type=int, default=100, help="Total requests")
    args = parser.parse_args()

    # Cek localhost sudah dihapus — untuk production testing

    total = args.requests
    per_thread = total // args.concurrent
    remainder = total % args.concurrent

    threads = []
    start_time = time.perf_counter()

    print(f"Load test: {args.url}")
    print(f"  Concurrent: {args.concurrent} threads")
    print(f"  Requests:   {total}\n")

    for i in range(args.concurrent):
        count = per_thread + (1 if i < remainder else 0)
        t = threading.Thread(target=worker, args=(args.url, count))
        threads.append(t)
        t.start()

    done = 0
    while done < total:
        time.sleep(0.3)
        with lock:
            done = len(results)
        print_progress(done, total)

    for t in threads:
        t.join()

    with lock:
        done = len(results)
    print_progress(done, total)
    print()

    elapsed = time.perf_counter() - start_time
    statuses = {}
    times = []
    sizes = []

    with lock:
        for s, t, sz in results:
            statuses[s] = statuses.get(s, 0) + 1
            times.append(t)
            sizes.append(sz)

    times.sort()
    avg = sum(times) / len(times) if times else 0
    rps = done / elapsed if elapsed > 0 else 0

    print(f"\n Hasil:")
    print(f"  Waktu:        {elapsed:.2f} detik")
    print(f"  Requests:     {done}")
    print(f"  RPS:          {rps:.1f} req/s")
    print(f"  Rata-rata:    {avg:.1f} ms")
    print(f"  Min:          {times[0]:.1f} ms" if times else "")
    print(f"  Max:          {times[-1]:.1f} ms" if times else "")
    print(f"  Median:       {times[len(times)//2]:.1f} ms" if times else "")
    print(f"  Status codes: {statuses}")

if __name__ == "__main__":
    main()
