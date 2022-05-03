export OC_PASS="test3588347"

evaluateHashResult(){
  expectedHash="$1"
  [[ ${SAVE_OUTPUTS:-0} == 1 ]]  && mkdir -p "${BATS_TEST_DIRNAME}/outputs"; echo "$3" > "${BATS_TEST_DIRNAME}/outputs/$(basename ${BATS_TEST_FILENAME})_${BATS_TEST_NUMBER}${4:-}.out"
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

dbQuery() {
    if [ "$DB_TYPE" == "mysql" ]; then
        mysql -h ${DB_HOST:-127.0.0.1} -u $DB_USER -p$DB_PASSWORD -P $DB_PORT -D $DATABASE -e "$1"
    elif [ "$DB_TYPE" == "pgsql" ]; then
        echo "${DB_HOST:-127.0.0.1}:$DB_PORT:$DATABASE:$DB_USER:$DB_PASSWORD" > ~/.pgpass
        chmod 0600 ~/.pgpass
        psql -h ${DB_HOST:-127.0.0.1} -U $DB_USER -p $DB_PORT -d $DATABASE -c "$1"
    else
        sqlite3 ./data/${SQLITE_DATABASE:-owncloud}.db "$1"
    fi
}

createTestFiles() {
    NO_OF_FILES=${1:-3}
    shift
    for path in "$@"
    do
        mkdir -p $path
    done

    #Include 0 to have one file per user (edge case where duplicate exist only because of one file per user)
    for i in $(seq 0 $NO_OF_FILES)
    do
        for j in $(seq 0 $i)
        do
            for path in "$@"
            do
                echo $i > $path/${i}_${j}.txt
            done
        done
    done
}

clearTestFiles(){
    rm -rf 'data/admin/files/tests' 'data/tuser/files/tests/' 'data/tuser/files/tests2' $@
    ./occ duplicates:clear -f
}