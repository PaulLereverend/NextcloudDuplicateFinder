#!/usr/bin/env bats
TESTSUITE="duplicatefinder"

setup() {
  ./occ app:disable duplicatefinder
}

@test "[$TESTSUITE] Enable App" {
  # Test for enable and migrations
  ./occ app:enable duplicatefinder
}
