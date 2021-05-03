#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load ${BATS_TEST_DIRNAME}/helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ duplicates:find-all
}

@test "[$TESTSUITE] List duplicates for user admin" {
  run ./occ -v duplicates:list -u admin
  [ "$status" -eq 0 ]

  expectedHash="d562d1652d009b7744fee753d40851a3910e2bd1bf6891c8244182fd965dfd31"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] List duplicates for all users" {
  run ./occ -v duplicates:list
  [ "$status" -eq 0 ]

  expectedHash="c974b701ec1109694a4b301587976d02391116625a2bc727b915021cd47b144a"
  evaluateHashResult "${expectedHash}" 26 "${output}"
}


@test "[$TESTSUITE] Check for user separation" {
  run ./occ -v duplicates:list -u tuser
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "admin" | wc -l ) -ne 0 ]]; then
    echo "Output is other than expected:"
    echo "$output"
    return 1
  fi
}
