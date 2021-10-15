#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ duplicates:clear -f
}

teardown(){
  clearTestFiles
}

@test "[$TESTSUITE] Test background jobs" {
  dbQuery "update oc_jobs set last_run=0,last_checked=0,reserved_at=0  where class like '%DuplicateFinder%';"
  
  php ./cron.php
  
  output=$(./occ -v duplicates:list)
  expectedHash="544e5bb0331f80508af24a26fcc27f75eee8a4a2540cc5db246bcca6a677d2f0"
  evaluateHashResult "${expectedHash}" 4 "${output}"
}
