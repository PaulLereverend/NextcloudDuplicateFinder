#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load ${BATS_TEST_DIRNAME}/helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
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

  expectedHash="8970518bc29c3b2b09d185145db3a0c5ed47a61f1e6221400d57332fa5f9c2fb"
  evaluateHashResult "${expectedHash}" 26 "${output}"
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

  expectedHash="ef8889fc687de9a10bff98fd4151484db25e68905decfae799268f6e06b83e37"
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
