#/bin/sh
cd `dirname $0` || exit
dir_name=`pwd`

start(){
    cluster_name=`cat .deploy/service.cluster.txt`

    cd config/
    if [ "$cluster_name" = "hna" ];   #hna
    then
	echo "default";
    elif [ "$cluster_name" = "hnq" ]; #hnq
    then
	echo "default";
    elif [ "${cluster_name%%-*}" = "us01" ]; #us
    then
        echo "us"
    fi
}

stop(){
    sleep 2s
}

case "$1" in
        start)
            #stop
            start
            echo "Done!"
            ;;
        stop)
            stop
            echo "Done!"
            ;;
           *)
           echo "Usage: $0 {start|stop}"
           ;;
esac
