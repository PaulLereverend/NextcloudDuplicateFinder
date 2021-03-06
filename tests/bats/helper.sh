export OC_PASS="test3588347"

evaluateHashResult(){
  expectedHash="$1"
  [[ ${SAVE_OUTPUTS:-0} == 1 ]]  && mkdir -p "${BATS_TEST_DIRNAME}/outputs"; echo "$3" > "${BATS_TEST_DIRNAME}/outputs/$(basename ${BATS_TEST_FILENAME})_${BATS_TEST_NUMBER}.out"
  realHash="$(echo "$3" | LC_ALL=C sort -h | sha256sum | awk '{ print $1 }')"
  if [[ $(echo "$3" | grep "file_hash" | wc -l ) -ne $2
      || "${realHash}" != "${expectedHash}" ]]; then
    echo "Output is other than expected:"
    echo "Count: $(echo "$3" | grep "file_hash" | wc -l )/$2"
    echo "Exepected: ${expectedHash}"
    echo "Result:    ${realHash}"
    echo "$3"
    return 1
  fi
}

randomString() {
    haveged -n $((${1:-32}*512)) --file - 2>/dev/null | tr -dc 'a-zA-Z0-9' | fold -w ${1:-32} | head -n 1
}
