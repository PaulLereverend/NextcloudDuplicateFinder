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
  groupShareId=$(createShare -d path="groupShareTest" -d shareType=1 -d shareWith=admin | grep '<id>' | sed 's/\s*<id>\(.*\)<\/id>/\1/')
  export userShareId groupShareId
}

teardown(){
    clearTestFiles 'data/tuser/files/groupShareTest' 'data/tuser/files/userShareTest'
    deleteShare $userShareId
    deleteShare $groupShareId
}

@test "[$TESTSUITE] Test for shared files" {
  output=$(./occ -v duplicates:find-all -u admin)
  expectedHash="07c5a378a726128905271919870b4d0422ed40e10bfb4df4708545a529d5885b"
  evaluateHashResult "${expectedHash}" 4 "${output}"
}
