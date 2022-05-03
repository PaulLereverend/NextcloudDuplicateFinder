#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ duplicates:find-all > /dev/null
}

teardown() {
    clearTestFiles
}

@test "[$TESTSUITE] Duplicate on deleted file" {
  rm -rf data/tuser/test3
  mkdir -p data/tuser/test3
  content="$(randomString)"
  echo "${content}" > data/tuser/test3/file_1.txt
  ./occ duplicates:find-all -u tuser -p test3
  echo "${content}" > data/tuser/test3/file_2.txt
  rm -rf data/tuser/test3/file_1.txt
  ouput="$(./occ duplicates:find-all -u tuser -p test3)"
  echo "${output}"
}
