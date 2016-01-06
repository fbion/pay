APP_HOME=`dirname $0`
JAVA_EXEC=java

CP="$APP_HOME/"
CP="$CP:$APP_HOME/lib/*"

$JAVA_EXEC -Xmx512M -XX:MaxPermSize=192m -cp $CP com.yeepay.g3.utils.security.cfca.rs.RestMain "$@"
