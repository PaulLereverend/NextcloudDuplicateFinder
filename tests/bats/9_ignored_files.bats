#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  export MAX_FILES=3
  load ${BATS_TEST_DIRNAME}/setup.sh
  createTestFiles $MAX_FILES
}

teardown(){
  clearTestFiles
  ./occ config:app:delete duplicatefinder ignored_files
}

evaluateSQLHashResult(){
  output=$(dbQuery "select path from oc_duplicatefinder_finfo where file_hash is null;")
  evaluateHashResult "$1" 0 "$(echo "$output" | grep files | sed 's/^\s*//')" "_1"
}

@test "[$TESTSUITE] Test for ignored files by filename" {
  ./occ config:app:set --value='[[{"attribute":"filename","operator":"=","value":"1_1.txt"}]]' duplicatefinder ignored_files
  output=$(./occ -v duplicates:find-all)
  expectedHash="aedb51b83e2871f1539bfa1983a802920b8f96440d29aebf9c182504b667656f"
  evaluateHashResult "${expectedHash}" 6 "${output}"
  output=$(dbQuery "select path from oc_duplicatefinder_finfo where file_hash is null;")
  expectedHash="f2f0ef7933eff6f757d413c9e8f221d7d9dff790077d7cc881cf15cbbad0e584"
  evaluateSQLHashResult "${expectedHash}"
}

@test "[$TESTSUITE] Test for ignored files by file size" {
  ./occ config:app:set --value='[[{"attribute":"size","operator":">=","value":6}]]' duplicatefinder ignored_files
  echo "3333" >> data/admin/files/tests/3_0.txt
  echo "3333" >> data/tuser/files/tests/3_0.txt
  echo "333" >> data/admin/files/tests/3_1.txt
  echo "333" >> data/tuser/files/tests/3_1.txt
  echo "33" >> data/admin/files/tests/3_2.txt
  output=$(./occ -v duplicates:find-all)
  expectedHash="e396bb82cb218afe5e8a0e046357b65f48e3a7d7e0862700f0c16d4e684f1a07"
  evaluateHashResult "${expectedHash}" 6 "${output}"
  expectedHash="33d9d47bad51964d0483d1f40d63c16470d118dad516bb0e90492aa37888d589"
  evaluateSQLHashResult "${expectedHash}"
}

@test "[$TESTSUITE] Test for ignored files by file path" {
  ./occ config:app:set --value='[[{"attribute":"path","operator":"GLOB","value":"*tests2*"}]]' duplicatefinder ignored_files
  output=$(./occ -v duplicates:find-all)
  expectedHash="761e12e5bc66db8141c6c98f54ff83789f0206f38cb2285e175cfe07703671b0"
  evaluateHashResult "${expectedHash}" 6 "${output}"
  output=$(dbQuery "select path from oc_duplicatefinder_finfo where file_hash is null;")
  expectedHash="a20b516aed56abe1774d75044095d5e82a7900a73f086b66839943f8e8fe7234"
  evaluateSQLHashResult "${expectedHash}"
}