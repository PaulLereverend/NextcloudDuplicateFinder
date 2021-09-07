#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ duplicates:clear -f
}

@test "[$TESTSUITE] Test background jobs" {
  dbQuery "update oc_jobs set last_run=0,last_checked=0,reserved_at=0  where class like '%DuplicateFinder%';"
  php ./cron.php
  run ./occ -v duplicates:list
  [ "$status" -eq 0 ]

  expectedHash="37df47434c76bd4387c975aba58906ea1c0e86be6e8e7d3f7ad329017f5a9187"
  evaluateHashResult "${expectedHash}" 26 "${output}"
}
