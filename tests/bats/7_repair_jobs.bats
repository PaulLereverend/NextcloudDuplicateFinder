#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ -v duplicates:find-all
}

teardown(){
    ./occ duplicates:clear -f
}

@test "[$TESTSUITE] Test Repair Path Hashes Job" {
    dbQuery "update oc_duplicatefinder_finfo set path_hash='';"

    ./occ maintenance:repair

    out="$(dbQuery "select id from oc_duplicatefinder_finfo where path_hash='';")"
    if [[ "$(echo "$out" | wc -l )" -gt 0 ]]; then
        echo "Repairing failed:\n$out"
        return 0
    fi

}

@test "[$TESTSUITE] Test Repair Duplicates Job" {
    dbQuery "update oc_duplicatefinder_finfo set path_hash='';"
    ./occ -v duplicates:find-all
    run ./occ -v duplicates:list
    [ "$status" -eq 0 ]
    expectedHash="f6e92d51240a4aa74f1b42bdee4eabb9377400503a70c57703baaeb68cdb63a0"
    evaluateHashResult "${expectedHash}" 26 "${output}" "_1"

    ./occ maintenance:repair

    run ./occ -v duplicates:list
    [ "$status" -eq 0 ]
    expectedHash="37df47434c76bd4387c975aba58906ea1c0e86be6e8e7d3f7ad329017f5a9187"
    evaluateHashResult "${expectedHash}" 26 "${output}"
}

@test "[$TESTSUITE] Test Repair Duplicates Job for one user" {
    dbQuery "update oc_duplicatefinder_finfo set path_hash='';"
    ./occ -v duplicates:find-all
    ./occ maintenance:repair

    run ./occ -v duplicates:list -u admin
    [ "$status" -eq 0 ]
    expectedHash="8cd80ef1a6646606b7103aaefc8bb6da30a153d48d377daf87f34001948a0f8b"
    evaluateHashResult "${expectedHash}" 25 "${output}" "_2"
}
