# PickUpMyDonation.com (Bedrock)

The Roots Bedrock site behind https://www.pickupmydonation.com. The donation intake itself is the Donation Manager plugin (`SMCoThrift/donation-manager`), developed in `web/app/plugins/localdev-donation-manager`; its own `AGENTS.md` covers that repo and its release flow. `README.md` covers the local Donation Manager setup.

## Where things live

- **Development home:** the Mac Mini, at `~/webdev/bedrock/pickupmydonation.com`, served by Valet as `https://pickupmydonation.test` on PHP 8.4 (`valet isolate`). Mail is caught by Mailpit. The partner API constants (`CHHJ_*`, `GOTJUNK_*`) stay unset locally, so nothing is sent to partners.
- **Production:** SpinupWP server web2.pickupmydonation.com (159.203.174.70), site user `pickupmydonation`, site root `/sites/pickupmydonation.com/files`. The canonical host is `www.`; the bare domain 301s to it, so `curl` checks against production need `-L`.
- **Direct `ssh pickupmydonation@159.203.174.70` is allowed** for this site (Michael, 2026-10-14), beyond the read-only `spinuptui ssh-exec` gate. Production changes still need his go-ahead.

## Deploys

Pushing `main` deploys production (SpinupWP Push-to-Deploy). It lands within about 10 seconds. The deploy script is `composer install -o --no-dev`, then `bower install` in `web/app/plugins/donation-manager`.

- Confirm before pushing `main` unless a deploy was asked for. Afterwards, check production: the Donation Manager version, `wp option get donman_db_version`, and healthz.
- A Donation Manager release reaches production by bumping the exact pin in `composer.json`, running `composer update smcothrift/donation-manager`, and committing `Updating Donation Manager (A.B.C => X.Y.Z)`.
- The cache drop-ins (`web/app/object-cache.php`, `web/app/advanced-cache.php`) aren't tracked, are gitignored, and survive deploys. The Redis object cache has been on since 2026-10-14.
- `~/files/update-donation-manager.sh` on the server is stale (pre-Bedrock `public/` layout). Ignore it.

## Production server settings applied by hand

Applied on web2 on 2026-10-14, after a slow admin grid tied up all 6 static PHP-FPM workers:

- **MySQL 8.0**, via `SET PERSIST` (stored in the datadir's `mysqld-auto.cnf`, outside SpinupWP's config): `innodb_buffer_pool_size` 768 MB, `slow_query_log=ON`, `long_query_time=2`. The slow log is `/var/lib/mysql/web2-slow.log`.
- **PHP-FPM pool** `/etc/php/8.4/fpm/pool.d/pickupmydonation.conf`, three lines after `pm.max_requests`: `request_terminate_timeout = 120s`, `request_slowlog_timeout = 10s`, `slowlog = /sites/pickupmydonation.com/logs/php-slow.log`. **Check they're still there after any PHP change in the SpinupWP dashboard**; it isn't known whether a dashboard save rewrites the pool file. Don't put custom settings in `.spinupwp-pool.conf`, which SpinupWP manages.
- Both slow logs are root-only. `/etc/cron.d/pmd-slowlog-copy` copies them every 15 minutes to `logs/review/`, where the site user can read them.
- **WP-Cron is off** (`DISABLE_WP_CRON=true`). Cron and long jobs run through WP-CLI from the site user's crontab, so the FPM terminate timeout can't hit them.
- Nobody has sudo on web2. A root change needs Michael, or a temporary sudo user created in SpinupWP.

## Data notes

- **Production keeps donation posts only from 2026-06-01.** Daily partner and donation stats start then; orphaned-donation stats go back to 2022. Archived days keep their `donman_stats` rows.
- **"Click to Claim" (`click_timestamp`) was turned off on purpose** on 2023-12-07 (plugin commit `3afa964`), at Jake's request after complaints (PMD to-do 6820012589). Claim data stops then. That's not a bug.
- **Run analyses against the local copy, not production.** A whole-range postmeta join on production took 41 seconds; refresh the local database first (`spinuptui pull db`). For metro analyses, rural zips have MSA code `0000`, so group them by county.

## Orphan follow-up email and priority upgrade

Since Donation Manager 5.12.0 (2026-11-03), orphaned donors who declined fee-based pickup (`fee_based` = `0`) get one email about 48 hours later, offering the priority partner(s) in their zip. Zips without a priority partner get nothing. The email's button opens `/priority-upgrade/` (page 1055076, `[priority-upgrade]`), and confirming there moves the **same** donation to the partner and sends it as a priority donation.

- **On/off and delay:** Donation Manager → General → Orphan Follow-up Email. Turning it off stops new emails immediately.
- **Runs from the site user's crontab** at :07, :22, :37 and :52: `wp dm orphan-followup`, one batch of up to 50, one summary line per run appended to `logs/orphan-followup.log`. `wp dm orphan-followup --dry-run` lists who's due; `--donation=<id> --to=<email>` sends a test copy without marking the donation. Never click Confirm on a real donor's link: it sends their donation to the partner.
- **Meta:** `_orphan_followup_sent` or `_orphan_followup_skipped` (`no-partner`, `no-email`) on every donation it looks at. An upgraded donation has `_priority_upgraded_at`, `_priority_upgrade_source` = `orphan-followup`, and its original org and trans dept in `_priority_upgraded_from_org` and `_priority_upgraded_from_trans_dept`.
- **Stats:** the "Orphaned Donation Stats" Dashboard widget. Upgrades also count in the partner rows of the main stats, on the day the donation was submitted.
- The commented-out `followup-emails/` script in the crontab (a 2023 version that emailed every orphan, off since March 2025) was replaced by this job. The previous crontab is saved at `/sites/pickupmydonation.com/crontab-backup-20261103.txt`.

## Priority zip reassignment

Priority zips are `pickup_code` terms on `trans_dept` posts. An organization's `pickup_settings.priority_pickup` makes it a priority partner, and `pickup_settings_donation_routing` sets delivery (`email` or `1800gj_api`). Key IDs:

| Partner | trans_dept | org | Notes |
|---|---|---|---|
| 1-800-GOT-JUNK? (National) | 642023 | 521689 | About 17,000 zips, too many to edit in wp-admin |
| CR Donations | 466820 | 466821 | Email routing |

Reassignment requests recur (on 2026-10-23, 125 Pittsburgh/Cleveland zips moved from GOT-JUNK to CR Donations for Jake). The script that did it, with its snapshots, is on production in `/sites/pickupmydonation.com/zip-reassign-20261023/`. It snapshots, removes and appends terms, then verifies via `get_priority_organizations`. To reuse it, change the `FROM_TD`/`TO_TD` constants and the expected count, then snapshot, dry-run, and run it locally before production. `wp eval-file` rejects `--flag` style arguments, so pass plain words (for example `dry-run`).
