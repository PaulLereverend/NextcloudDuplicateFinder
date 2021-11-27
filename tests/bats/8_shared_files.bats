#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

createShare() {
  curl -u "tuser:${OC_PASS}" -H "OCS-APIRequest: true" "${NEXTCLOUD}/ocs/v1.php/apps/files_sharing/api/v1/shares" $@
}

deleteShare() {
  shareId=$1
  shift
  curl -X DELETE -u "tuser:${OC_PASS}" -H "OCS-APIRequest: true" "${NEXTCLOUD}/ocs/v1.php/apps/files_sharing/api/v1/shares/${shareId}" $@
}

setup() {
  if [ -z "$NEXTCLOUD" ]; then
    echo "environment variable NEXTCLOUD need to bee defined"
    exit 1
  fi
  ./occ app:enable files_sharing

  export MAX_FILES=3
  load ${BATS_TEST_DIRNAME}/setup.sh
  createTestFiles $MAX_FILES \
    'data/tuser/files/userShareTest' \
    'data/tuser/files/userShareTest/subdir' \
    'data/tuser/files/userShareTest/subdir/subsubdir' \
    'data/tuser/files/groupShareTest' \
    'data/tuser/files/groupShareTest/subdir'\
    'data/tuser/files/groupShareTest/subdir/subsubdir'

  
  userShareId=$(createShare -d path="userShareTest" -d shareType=0 -d shareWith=admin | grep '<id>' | sed 's/\s*<id>\(.*\)<\/id>/\1/')
  userSingleShareId=$(createShare -d path="userShareTest/0_0.txt" -d shareType=0 -d shareWith=admin | grep '<id>' | sed 's/\s*<id>\(.*\)<\/id>/\1/')
  groupShareId=$(createShare -d path="groupShareTest" -d shareType=1 -d shareWith=admin | grep '<id>' | sed 's/\s*<id>\(.*\)<\/id>/\1/')
  export userShareId groupShareId userSingleShareId
}

teardown(){
    clearTestFiles 'data/tuser/files/groupShareTest' 'data/tuser/files/userShareTest'
    deleteShare $userShareId
    deleteShare $groupShareId
    deleteShare $userSingleShareId
    # echo 'Skipped'
}

@test "[$TESTSUITE] Test for shared files" {
  output=$(./occ -v duplicates:find-all -u admin)
  expectedHash="2ca5c9505456931ec0d6a8ba6f241da615c46b73bf4bdf3b8c925c588149b952"
  evaluateHashResult "${expectedHash}" 4 "${output}"
}

@test "[$TESTSUITE] Test for listing shared files" {
  ./occ -v duplicates:find-all -u admin
  output=$(./occ -v duplicates:list -u admin)
  expectedHash="2b14b6a34d4103de82abc1f5d6efb1b6bbf02c0df4fbca44132c70da095b7182"
  evaluateHashResult "${expectedHash}" 4 "${output}"
}
