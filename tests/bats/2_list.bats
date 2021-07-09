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

  expectedHash="8cd80ef1a6646606b7103aaefc8bb6da30a153d48d377daf87f34001948a0f8b"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] List duplicates for all users" {
  run ./occ -v duplicates:list
  [ "$status" -eq 0 ]

  expectedHash="37df47434c76bd4387c975aba58906ea1c0e86be6e8e7d3f7ad329017f5a9187"
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
