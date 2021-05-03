#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ duplicates:find-all > /dev/null
}

@test "[$TESTSUITE] Clear all" {
  ./occ duplicates:clear -f
  run ./occ -v duplicates:list
  [ "$status" -eq 0 ]

  expectedHash="9cd4d85c76b5277321cf1de8f132f8b27d2a35a284b0ba10d4c9171b10eed00b"
  evaluateHashResult "${expectedHash}" 0 "${output}"
}
