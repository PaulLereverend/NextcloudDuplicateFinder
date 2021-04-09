#!/usr/bin/env bats
TESTSUITE="Duplicates"

setup() {
  if [[ -z $(./occ user:list | grep "tuser:") ]]; then
    export OC_PASS="test3588347"
    ./occ user:add --password-from-env  --display-name="Test User" tuser
  fi

  mkdir -p data/{admin,tuser}/files/tests
  #Include 0 to have one file per user (edge case where duplicate exist only because of one file per user)
  for i in $(seq 0 25)
  do
    for j in $(seq 0 $i)
    do
      echo $i > data/admin/files/tests/${i}_${j}.txt
      echo $i > data/tuser/files/tests/${i}_${j}.txt
    done
  done

  ./occ files:scan --all
}

@test "[$TESTSUITE] Scan for duplicates" {
  run ./occ -v duplicate:find-all -u admin -p /tests
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_hash" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "508d4c65b4498a6df106d97848e065328461a1049326664ab870f8e639710ffd" ]]; then
    echo "Output is other than expected."
    echo "Count: $(echo "$output" | grep "file_hash" | wc -l )"
    echo "Exepected: 508d4c65b4498a6df106d97848e065328461a1049326664ab870f8e639710ffd"
    echo "Result:    $(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')"
    echo "$output"

    return 1
  fi
}

@test "[$TESTSUITE] List duplicates" {
  run ./occ -v duplicate:list -u admin
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_hash" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "58d7d38903a42b7a92849784c596548a720888bd59f799c08f94523d59ae4164" ]]; then
    echo "Output is other than expected:"
    echo "Count: $(echo "$output" | grep "file_hash" | wc -l )"
    echo "Exepected: 58d7d38903a42b7a92849784c596548a720888bd59f799c08f94523d59ae4164"
    echo "Result:    $(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')"
    echo "$output"
    return 1
  fi
}


@test "[$TESTSUITE] Check for user separation" {
  run ./occ -v duplicate:find-all -u tuser
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "admin" | wc -l ) -ne 0 ]]; then
    echo "Output is other than expected:"
    echo "$output"
    return 1
  fi
}
