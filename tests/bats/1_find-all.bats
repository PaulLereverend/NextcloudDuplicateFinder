#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load ${BATS_TEST_DIRNAME}/helper.sh

setup() {
  export MAX_FILES=25
  load ${BATS_TEST_DIRNAME}/setup.sh
}

teardown() {
    clearTestFiles
}

@test "[$TESTSUITE] Scan duplicates for user" {
  run ./occ -v duplicates:find-all -u admin
  [ "$status" -eq 0 ]

  expectedHash="19fb85351e9ef4cbca4b165043d303fd723413fb3df6e0929d34a0e83c025142"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for path" {
  run ./occ -v duplicates:find-all -p ./tests
  [ "$status" -eq 0 ]

  expectedHash="1657beda4e8910b33f1c21b494446e25896a7e30eea1dfb565c6e24eb18b29d6"
  # 51 because tuser hash file "zero" two times
  evaluateHashResult "${expectedHash}" 50 "${output}"
}

@test "[$TESTSUITE] Scan duplicates for user and path" {
  run ./occ -v duplicates:find-all -u tuser -p tests2
  [ "$status" -eq 0 ]

  expectedHash="32af17b6db3a8496229b740562938355d83edad67162dfb5f3843e50f4600a56"
  evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] Scan for all duplicates" {
  run ./occ -v duplicates:find-all
  [ "$status" -eq 0 ]

  expectedHash="543123426997192c38e02cf08ab676059e60fcc9efbfc41ff04a60b61da907f7"
  evaluateHashResult "${expectedHash}" 51 "${output}"
}

@test "[$TESTSUITE] Scan path that doesn't exist" {
  run ./occ -v duplicates:find-all -p path_not_found
  [ "$status" -eq 0 ]

  expectedHash="2c5f884c3b78c7ba08baee430af449eaa6d9c4b97f1609481a2bc346d28e69dc"
  evaluateHashResult "${expectedHash}" 0 "${output}"
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
