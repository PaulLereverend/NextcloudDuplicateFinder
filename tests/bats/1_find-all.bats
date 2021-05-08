#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load ${BATS_TEST_DIRNAME}/helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
}

@test "[$TESTSUITE] Scan duplicates for user" {
  run ./occ -v duplicates:find-all -u admin
  [ "$status" -eq 0 ]

  expectedHash="6d83990f6de7db72e63a68468e80bc7f091452d5be33ff677327409d5f8143b9"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for path" {
  run ./occ -v duplicates:find-all -p ./tests
  [ "$status" -eq 0 ]

  expectedHash="68a757b39206d7417aa06323731207c2da06dd968163efc7f46df7e814605263"
  # 51 because tuser hash file "zero" two times
  evaluateHashResult "${expectedHash}" 50 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for user and path" {
  run ./occ -v duplicates:find-all -u tuser -p tests2
  [ "$status" -eq 0 ]

  expectedHash="9d057f8673fbb54276dc31ca27d7da7b6a5cdf0d4d532dfd3ed7b14536e425fc"
  evaluateHashResult "${expectedHash}" 26 "${output}"
}

@test "[$TESTSUITE] Scan for all duplicates" {
  run ./occ -v duplicates:find-all
  [ "$status" -eq 0 ]

  expectedHash="7f95571afc0fd23defbe4f15ec123115d986a9b2cdcc745caf45bba606a5f71f"
  evaluateHashResult "${expectedHash}" 51 "${output}"
}

@test "[$TESTSUITE] Scan path that doesn't exist" {
  run ./occ -v duplicates:find-all -p path_not_found
  [ "$status" -eq 0 ]

  expectedHash="8af3056a4f99c58cb0fb4070dd84becf5d1d50d7a5bc84dac319b2a23067fec7"
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
