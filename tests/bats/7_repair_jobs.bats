#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ -v duplicates:find-all
}

@test "[$TESTSUITE] Test Repair Job" {
    dbQuery "update oc_duplicatefinder_finfo set path_hash='';"

    ./occ maintenance:repair

    out="$(dbQuery "select id from oc_duplicatefinder_finfo where path_hash='';")"
    if [[ "$(echo "$out" | wc -l )" -gt 0 ]]; then
        echo "Repairing failed:\n$out"
        return 0
    fi

}
