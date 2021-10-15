#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ -v duplicates:find-all
}

teardown() {
    clearTestFiles
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
    output=$(./occ -v duplicates:list)
    expectedHash="db1f0ecb4096a811ccf6b50be9af8cca537f5f08c4667ba1b2f6f7e5b6dced41"
    evaluateHashResult "${expectedHash}" 4 "${output}" "_1"

    ./occ maintenance:repair

    output=$(./occ -v duplicates:list)
    expectedHash="544e5bb0331f80508af24a26fcc27f75eee8a4a2540cc5db246bcca6a677d2f0"
    evaluateHashResult "${expectedHash}" 4 "${output}"
}

@test "[$TESTSUITE] Test Repair Duplicates Job for one user" {
    dbQuery "update oc_duplicatefinder_finfo set path_hash='';"
    ./occ -v duplicates:find-all
    ./occ maintenance:repair

    run ./occ -v duplicates:list -u admin
    [ "$status" -eq 0 ]
    expectedHash="0a3afe9180daf73432d6dbf0c6a1bb6b215404b971a3e00f4d005f68b2272587"
    evaluateHashResult "${expectedHash}" 3 "${output}" "_2"
}
