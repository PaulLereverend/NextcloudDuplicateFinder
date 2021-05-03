#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load ${BATS_TEST_DIRNAME}/helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
}

@test "[$TESTSUITE] Scan duplicates for user" {
  run ./occ -v duplicates:find-all -u admin
  [ "$status" -eq 0 ]

  expectedHash="9560fbcf1d1e167d4a883a32057b9f526fe791a5aff0eaa967a6dfc41abcf96d"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for path" {
  run ./occ -v duplicates:find-all -p ./tests
  [ "$status" -eq 0 ]

  expectedHash="fb7ff7b5f227ee42bae5071c2fefc3bbf5a11b2e1a017d2b54ab4172ac478f60"
  # 51 because tuser hash file "zero" two times
  evaluateHashResult "${expectedHash}" 50 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for user and path" {
  run ./occ -v duplicates:find-all -u tuser -p tests2
  [ "$status" -eq 0 ]

  expectedHash="0f34226fbfcd0808a1ef955ff7bdad3141580d9b508e7ded4ba368b10f15cbf5"
  evaluateHashResult "${expectedHash}" 26 "${output}"
}

@test "[$TESTSUITE] Scan for all duplicates" {
  run ./occ -v duplicates:find-all
  [ "$status" -eq 0 ]

  expectedHash="bf50f16cb96e7a63aef929dd4445f118343f165198d31d2600792337b7b5770d"
  evaluateHashResult "${expectedHash}" 51 "${output}"
}

@test "[$TESTSUITE] Scan path that doesn't exist" {
  run ./occ -v duplicates:find-all -p path_not_found
  [ "$status" -eq 0 ]

  expectedHash="4ea6ca16a40ed013de2d09c3103d92f2b3790ca5ed9867050a9f265c05cb3eea"
  evaluateHashResult "${expectedHash}" 51 "${output}"
}

@test "[$TESTSUITE] Check for user separation" {
  run ./occ -v duplicates:find-all -u tuser
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "admin" | wc -l ) -ne 0 ]]; then
    echo "Output is other than expected:"
    echo "$output"
    return 1
  fi
}
