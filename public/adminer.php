<?php
/** Adminer - Compact database management
 * @link https://www.adminer.org/
 * @author Jakub Vrana, https://www.vrana.cz/
 * @copyright 2007 Jakub Vrana
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 * @version 5.2.1
 */ namespace Adminer;
const VERSION = "5.2.1";
error_reporting(24575);
set_error_handler(function ($pc, $rc) {
    return !!preg_match("~^Undefined (array key|offset|index)~", $rc);
}, E_WARNING | E_NOTICE);
$Kc = !preg_match('~^(unsafe_raw)?$~', ini_get("filter.default"));
if ($Kc || ini_get("filter.default_flags")) {
    foreach (["_GET", "_POST", "_COOKIE", "_SERVER"] as $X) {
        $ci = filter_input_array(constant("INPUT$X"), FILTER_UNSAFE_RAW);
        if ($ci) {
            $$X = $ci;
        }
    }
}
if (function_exists("mb_internal_encoding")) {
    mb_internal_encoding("8bit");
}
function connection($f = null)
{
    return $f ?: Db::$Id;
}
function adminer()
{
    return Adminer::$Id;
}
function driver()
{
    return Driver::$Id;
}
function connect()
{
    $ub = adminer()->credentials();
    $K = Driver::connect($ub[0], $ub[1], $ub[2]);
    return is_object($K) ? $K : null;
}
function idf_unescape($v)
{
    if (!preg_match('~^[`\'"[]~', $v)) {
        return $v;
    }
    $ae = substr($v, -1);
    return str_replace($ae . $ae, $ae, substr($v, 1, -1));
}
function q($gh)
{
    return connection()->quote($gh);
}
function escape_string($X)
{
    return substr(q($X), 1, -1);
}
function idx($ra, $y, $j = null)
{
    return $ra && array_key_exists($y, $ra) ? $ra[$y] : $j;
}
function number($X)
{
    return preg_replace("~[^0-9]+~", "", $X);
}
function number_type()
{
    return "((?<!o)int(?!er)|numeric|real|float|double|decimal|money)";
}
function remove_slashes(array $bg, $Kc = false)
{
    if (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
        while ([$y, $X] = each($bg)) {
            foreach ($X as $Td => $W) {
                unset($bg[$y][$Td]);
                if (is_array($W)) {
                    $bg[$y][stripslashes($Td)] = $W;
                    $bg[] = &$bg[$y][stripslashes($Td)];
                } else {
                    $bg[$y][stripslashes($Td)] = $Kc ? $W : stripslashes($W);
                }
            }
        }
    }
}
function bracket_escape($v, $za = false)
{
    static $Ph = [":" => ":1", "]" => ":2", "[" => ":3", '"' => ":4"];
    return strtr($v, $za ? array_flip($Ph) : $Ph);
}
function min_version($ri, $oe = "", $f = null)
{
    $f = connection($f);
    $Lg = $f->server_info;
    if ($oe && preg_match("~([\d.]+)-MariaDB~", $Lg, $B)) {
        $Lg = $B[1];
        $ri = $oe;
    }
    return $ri && version_compare($Lg, $ri) >= 0;
}
function charset(Db $e)
{
    return min_version("5.5.3", 0, $e) ? "utf8mb4" : "utf8";
}
function ini_bool($Dd)
{
    $X = ini_get($Dd);
    return preg_match('~^(on|true|yes)$~i', $X) || (int) $X;
}
function sid()
{
    static $K;
    if ($K === null) {
        $K = SID && !($_COOKIE && ini_bool("session.use_cookies"));
    }
    return $K;
}
function set_password($qi, $P, $V, $G)
{
    $_SESSION["pwds"][$qi][$P][$V] =
        $_COOKIE["adminer_key"] && is_string($G)
            ? [encrypt_string($G, $_COOKIE["adminer_key"])]
            : $G;
}
function get_password()
{
    $K = get_session("pwds");
    if (is_array($K)) {
        $K = $_COOKIE["adminer_key"]
            ? decrypt_string($K[0], $_COOKIE["adminer_key"])
            : false;
    }
    return $K;
}
function get_val($I, $l = 0, $kb = null)
{
    $kb = connection($kb);
    $J = $kb->query($I);
    if (!is_object($J)) {
        return false;
    }
    $L = $J->fetch_row();
    return $L ? $L[$l] : false;
}
function get_vals($I, $c = 0)
{
    $K = [];
    $J = connection()->query($I);
    if (is_object($J)) {
        while ($L = $J->fetch_row()) {
            $K[] = $L[$c];
        }
    }
    return $K;
}
function get_key_vals($I, $f = null, $Og = true)
{
    $f = connection($f);
    $K = [];
    $J = $f->query($I);
    if (is_object($J)) {
        while ($L = $J->fetch_row()) {
            if ($Og) {
                $K[$L[0]] = $L[1];
            } else {
                $K[] = $L[0];
            }
        }
    }
    return $K;
}
function get_rows($I, $f = null, $k = "<p class='error'>")
{
    $kb = connection($f);
    $K = [];
    $J = $kb->query($I);
    if (is_object($J)) {
        while ($L = $J->fetch_assoc()) {
            $K[] = $L;
        }
    } elseif (
        !$J &&
        !$f &&
        $k &&
        (defined("Adminer\PAGE_HEADER") || $k == "-- ")
    ) {
        echo $k . error() . "\n";
    }
    return $K;
}
function unique_array($L, array $x)
{
    foreach ($x as $w) {
        if (preg_match("~PRIMARY|UNIQUE~", $w["type"])) {
            $K = [];
            foreach ($w["columns"] as $y) {
                if (!isset($L[$y])) {
                    continue 2;
                }
                $K[$y] = $L[$y];
            }
            return $K;
        }
    }
}
function escape_key($y)
{
    if (
        preg_match(
            "(^([\w(]+)(" .
                str_replace("_", ".*", preg_quote(idf_escape("_"))) .
                ')([ \w)]+)$)',
            $y,
            $B,
        )
    ) {
        return $B[1] . idf_escape(idf_unescape($B[2])) . $B[3];
    }
    return idf_escape($y);
}
function where(array $Z, array $m = [])
{
    $K = [];
    foreach ((array) $Z["where"] as $y => $X) {
        $y = bracket_escape($y, true);
        $c = escape_key($y);
        $l = idx($m, $y, []);
        $Ic = $l["type"];
        $K[] =
            $c .
            (JUSH == "sql" && $Ic == "json"
                ? " = CAST(" . q($X) . " AS JSON)"
                : (JUSH == "sql" && is_numeric($X) && preg_match("~\.~", $X)
                    ? " LIKE " . q($X)
                    : (JUSH == "mssql" && strpos($Ic, "datetime") === false
                        ? " LIKE " . q(preg_replace("~[_%[]~", '[\0]', $X))
                        : " = " . unconvert_field($l, q($X)))));
        if (
            JUSH == "sql" &&
            preg_match("~char|text~", $Ic) &&
            preg_match("~[^ -@]~", $X)
        ) {
            $K[] =
                "$c = " . q($X) . " COLLATE " . charset(connection()) . "_bin";
        }
    }
    foreach ((array) $Z["null"] as $y) {
        $K[] = escape_key($y) . " IS NULL";
    }
    return implode(" AND ", $K);
}
function where_check($X, array $m = [])
{
    parse_str($X, $Qa);
    remove_slashes([&$Qa]);
    return where($Qa, $m);
}
function where_link($t, $c, $Y, $hf = "=")
{
    return "&where%5B$t%5D%5Bcol%5D=" .
        urlencode($c) .
        "&where%5B$t%5D%5Bop%5D=" .
        urlencode($Y !== null ? $hf : "IS NULL") .
        "&where%5B$t%5D%5Bval%5D=" .
        urlencode($Y);
}
function convert_fields(array $d, array $m, array $O = [])
{
    $K = "";
    foreach ($d as $y => $X) {
        if ($O && !in_array(idf_escape($y), $O)) {
            continue;
        }
        $sa = convert_field($m[$y]);
        if ($sa) {
            $K .= ", $sa AS " . idf_escape($y);
        }
    }
    return $K;
}
function cookie($D, $Y, $ie = 2592000)
{
    header(
        "Set-Cookie: $D=" .
            urlencode($Y) .
            ($ie
                ? "; expires=" . gmdate("D, d M Y H:i:s", time() + $ie) . " GMT"
                : "") .
            "; path=" .
            preg_replace("~\?.*~", "", $_SERVER["REQUEST_URI"]) .
            (HTTPS ? "; secure" : "") .
            "; HttpOnly; SameSite=lax",
        false,
    );
}
function get_settings($qb)
{
    parse_str($_COOKIE[$qb], $Pg);
    return $Pg;
}
function get_setting($y, $qb = "adminer_settings")
{
    $Pg = get_settings($qb);
    return $Pg[$y];
}
function save_settings(array $Pg, $qb = "adminer_settings")
{
    $Y = http_build_query($Pg + get_settings($qb));
    cookie($qb, $Y);
    $_COOKIE[$qb] = $Y;
}
function restart_session()
{
    if (
        !ini_bool("session.use_cookies") &&
        (!function_exists("session_status") || session_status() == 1)
    ) {
        session_start();
    }
}
function stop_session($Pc = false)
{
    $ki = ini_bool("session.use_cookies");
    if (!$ki || $Pc) {
        session_write_close();
        if ($ki && @ini_set("session.use_cookies", "0") === false) {
            session_start();
        }
    }
}
function &get_session($y)
{
    return $_SESSION[$y][DRIVER][SERVER][$_GET["username"]];
}
function set_session($y, $X)
{
    $_SESSION[$y][DRIVER][SERVER][$_GET["username"]] = $X;
}
function auth_url($qi, $P, $V, $i = null)
{
    $gi = remove_from_uri(
        implode("|", array_keys(SqlDriver::$Ub)) .
            "|username|ext|" .
            ($i !== null ? "db|" : "") .
            ($qi == "mssql" || $qi == "pgsql" ? "" : "ns|") .
            session_name(),
    );
    preg_match("~([^?]*)\??(.*)~", $gi, $B);
    return "$B[1]?" .
        (sid() ? SID . "&" : "") .
        ($qi != "server" || $P != ""
            ? urlencode($qi) . "=" . urlencode($P) . "&"
            : "") .
        ($_GET["ext"] ? "ext=" . urlencode($_GET["ext"]) . "&" : "") .
        "username=" .
        urlencode($V) .
        ($i != "" ? "&db=" . urlencode($i) : "") .
        ($B[2] ? "&$B[2]" : "");
}
function is_ajax()
{
    return $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
}
function redirect($A, $C = null)
{
    if ($C !== null) {
        restart_session();
        $_SESSION["messages"][
            preg_replace(
                "~^[^?]*~",
                "",
                $A !== null ? $A : $_SERVER["REQUEST_URI"],
            )
        ][] = $C;
    }
    if ($A !== null) {
        if ($A == "") {
            $A = ".";
        }
        header("Location: $A");
        exit();
    }
}
function query_redirect(
    $I,
    $A,
    $C,
    $jg = true,
    $wc = true,
    $Ec = false,
    $Ch = ""
) {
    if ($wc) {
        $ch = microtime(true);
        $Ec = !connection()->query($I);
        $Ch = format_time($ch);
    }
    $Xg = $I ? adminer()->messageQuery($I, $Ch, $Ec) : "";
    if ($Ec) {
        adminer()->error .= error() . $Xg . script("messagesPrint();") . "<br>";
        return false;
    }
    if ($jg) {
        redirect($A, $C . $Xg);
    }
    return true;
}
class Queries
{
    static $eg = [];
    static $ch = 0;
}
function queries($I)
{
    if (!Queries::$ch) {
        Queries::$ch = microtime(true);
    }
    Queries::$eg[] =
        (preg_match('~;$~', $I) ? "DELIMITER ;;\n$I;\nDELIMITER " : $I) . ";";
    return connection()->query($I);
}
function apply_queries($I, array $T, $sc = 'Adminer\table')
{
    foreach ($T as $R) {
        if (!queries("$I " . $sc($R))) {
            return false;
        }
    }
    return true;
}
function queries_redirect($A, $C, $jg)
{
    $eg = implode("\n", Queries::$eg);
    $Ch = format_time(Queries::$ch);
    return query_redirect($eg, $A, $C, $jg, false, !$jg, $Ch);
}
function format_time($ch)
{
    return sprintf("%.3f s", max(0, microtime(true) - $ch));
}
function relative_uri()
{
    return str_replace(
        ":",
        "%3a",
        preg_replace("~^[^?]*/([^?]*)~", '\1', $_SERVER["REQUEST_URI"]),
    );
}
function remove_from_uri($Af = "")
{
    return substr(
        preg_replace(
            "~(?<=[?&])($Af" . (SID ? "" : "|" . session_name()) . ")=[^&]*&~",
            "",
            relative_uri() . "&",
        ),
        0,
        -1,
    );
}
function get_file($y, $Fb = false, $Kb = "")
{
    $Jc = $_FILES[$y];
    if (!$Jc) {
        return null;
    }
    foreach ($Jc as $y => $X) {
        $Jc[$y] = (array) $X;
    }
    $K = "";
    foreach ($Jc["error"] as $y => $k) {
        if ($k) {
            return $k;
        }
        $D = $Jc["name"][$y];
        $Kh = $Jc["tmp_name"][$y];
        $mb = file_get_contents(
            $Fb && preg_match('~\.gz$~', $D) ? "compress.zlib://$Kh" : $Kh,
        );
        if ($Fb) {
            $ch = substr($mb, 0, 3);
            if (
                function_exists("iconv") &&
                preg_match("~^\xFE\xFF|^\xFF\xFE~", $ch)
            ) {
                $mb = iconv("utf-16", "utf-8", $mb);
            } elseif ($ch == "\xEF\xBB\xBF") {
                $mb = substr($mb, 3);
            }
        }
        $K .= $mb;
        if ($Kb) {
            $K .= (preg_match("($Kb\\s*\$)", $mb) ? "" : $Kb) . "\n\n";
        }
    }
    return $K;
}
function upload_error($k)
{
    $ve = $k == UPLOAD_ERR_INI_SIZE ? ini_get("upload_max_filesize") : 0;
    return $k
        ? "Unable to upload a file." .
                ($ve
                    ? " " . sprintf("Maximum allowed file size is %sB.", $ve)
                    : "")
        : "File does not exist.";
}
function repeat_pattern($If, $ge)
{
    return str_repeat("$If{0,65535}", $ge / 65535) .
        "$If{0," .
        $ge % 65535 .
        "}";
}
function is_utf8($X)
{
    return preg_match("~~u", $X) && !preg_match('~[\0-\x8\xB\xC\xE-\x1F]~', $X);
}
function format_number($X)
{
    return strtr(
        number_format($X, 0, ".", ","),
        preg_split("~~u", "0123456789", -1, PREG_SPLIT_NO_EMPTY),
    );
}
function friendly_url($X)
{
    return preg_replace("~\W~i", "-", $X);
}
function table_status1($R, $Fc = false)
{
    $K = table_status($R, $Fc);
    return $K ? reset($K) : ["Name" => $R];
}
function column_foreign_keys($R)
{
    $K = [];
    foreach (adminer()->foreignKeys($R) as $o) {
        foreach ($o["source"] as $X) {
            $K[$X][] = $o;
        }
    }
    return $K;
}
function fields_from_edit()
{
    $K = [];
    foreach ((array) $_POST["field_keys"] as $y => $X) {
        if ($X != "") {
            $X = bracket_escape($X);
            $_POST["function"][$X] = $_POST["field_funs"][$y];
            $_POST["fields"][$X] = $_POST["field_vals"][$y];
        }
    }
    foreach ((array) $_POST["fields"] as $y => $X) {
        $D = bracket_escape($y, true);
        $K[$D] = [
            "field" => $D,
            "privileges" => [
                "insert" => 1,
                "update" => 1,
                "where" => 1,
                "order" => 1,
            ],
            "null" => 1,
            "auto_increment" => $y == driver()->primary,
        ];
    }
    return $K;
}
function dump_headers($vd, $Ie = false)
{
    $K = adminer()->dumpHeaders($vd, $Ie);
    $yf = $_POST["output"];
    if ($yf != "text") {
        header(
            "Content-Disposition: attachment; filename=" .
                adminer()->dumpFilename($vd) .
                ".$K" .
                ($yf != "file" && preg_match('~^[0-9a-z]+$~', $yf)
                    ? ".$yf"
                    : ""),
        );
    }
    session_write_close();
    if (!ob_get_level()) {
        ob_start(null, 4096);
    }
    ob_flush();
    flush();
    return $K;
}
function dump_csv(array $L)
{
    foreach ($L as $y => $X) {
        if (preg_match('~["\n,;\t]|^0|\.\d*0$~', $X) || $X === "") {
            $L[$y] = '"' . str_replace('"', '""', $X) . '"';
        }
    }
    echo implode(
        $_POST["format"] == "csv"
            ? ","
            : ($_POST["format"] == "tsv"
                ? "\t"
                : ";"),
        $L,
    ) . "\r\n";
}
function apply_sql_function($r, $c)
{
    return $r
        ? ($r == "unixepoch"
            ? "DATETIME($c, '$r')"
            : ($r == "count distinct" ? "COUNT(DISTINCT " : strtoupper("$r(")) .
                "$c)")
        : $c;
}
function get_temp_dir()
{
    $K = ini_get("upload_tmp_dir");
    if (!$K) {
        if (function_exists("sys_get_temp_dir")) {
            $K = sys_get_temp_dir();
        } else {
            $n = @tempnam("", "");
            if (!$n) {
                return "";
            }
            $K = dirname($n);
            unlink($n);
        }
    }
    return $K;
}
function file_open_lock($n)
{
    if (is_link($n)) {
        return;
    }
    $q = @fopen($n, "c+");
    if (!$q) {
        return;
    }
    chmod($n, 0660);
    if (!flock($q, LOCK_EX)) {
        fclose($q);
        return;
    }
    return $q;
}
function file_write_unlock($q, $_b)
{
    rewind($q);
    fwrite($q, $_b);
    ftruncate($q, strlen($_b));
    file_unlock($q);
}
function file_unlock($q)
{
    flock($q, LOCK_UN);
    fclose($q);
}
function first(array $ra)
{
    return reset($ra);
}
function password_file($g)
{
    $n = get_temp_dir() . "/adminer.key";
    if (!$g && !file_exists($n)) {
        return "";
    }
    $q = file_open_lock($n);
    if (!$q) {
        return "";
    }
    $K = stream_get_contents($q);
    if (!$K) {
        $K = rand_string();
        file_write_unlock($q, $K);
    } else {
        file_unlock($q);
    }
    return $K;
}
function rand_string()
{
    return md5(uniqid(strval(mt_rand()), true));
}
function select_value($X, $_, array $l, $Bh)
{
    if (is_array($X)) {
        $K = "";
        foreach ($X as $Td => $W) {
            $K .=
                "<tr>" .
                ($X != array_values($X) ? "<th>" . h($Td) : "") .
                "<td>" .
                select_value($W, $_, $l, $Bh);
        }
        return "<table>$K</table>";
    }
    if (!$_) {
        $_ = adminer()->selectLink($X, $l);
    }
    if ($_ === null) {
        if (is_mail($X)) {
            $_ = "mailto:$X";
        }
        if (is_url($X)) {
            $_ = $X;
        }
    }
    $K = adminer()->editVal($X, $l);
    if ($K !== null) {
        if (!is_utf8($K)) {
            $K = "\0";
        } elseif ($Bh != "" && is_shortable($l)) {
            $K = shorten_utf8($K, max(0, +$Bh));
        } else {
            $K = h($K);
        }
    }
    return adminer()->selectVal($K, $_, $l, $X);
}
function is_mail($fc)
{
    $ta = '[-a-z0-9!#$%&\'*+/=?^_`{|}~]';
    $Tb = "[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])";
    $If = "$ta+(\\.$ta+)*@($Tb?\\.)+$Tb";
    return is_string($fc) && preg_match("(^$If(,\\s*$If)*\$)i", $fc);
}
function is_url($gh)
{
    $Tb = "[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])";
    return preg_match(
        "~^(https?)://($Tb?\\.)+$Tb(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",
        $gh,
    );
}
function is_shortable(array $l)
{
    return preg_match(
        "~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~",
        $l["type"],
    );
}
function count_rows($R, array $Z, $Nd, array $s)
{
    $I = " FROM " . table($R) . ($Z ? " WHERE " . implode(" AND ", $Z) : "");
    return $Nd && (JUSH == "sql" || count($s) == 1)
        ? "SELECT COUNT(DISTINCT " . implode(", ", $s) . ")$I"
        : "SELECT COUNT(*)" .
                ($Nd
                    ? " FROM (SELECT 1$I GROUP BY " . implode(", ", $s) . ") x"
                    : $I);
}
function slow_query($I)
{
    $i = adminer()->database();
    $Dh = adminer()->queryTimeout();
    $Tg = driver()->slowQuery($I, $Dh);
    $f = null;
    if (!$Tg && support("kill")) {
        $f = connect();
        if ($f && ($i == "" || $f->select_db($i))) {
            $Vd = get_val(connection_id(), 0, $f);
            echo script(
                "const timeout = setTimeout(() => { ajax('" .
                    js_escape(ME) .
                    "script=kill', function () {}, 'kill=$Vd&token=" .
                    get_token() .
                    "'); }, 1000 * $Dh);",
            );
        }
    }
    ob_flush();
    flush();
    $K = @get_key_vals($Tg ?: $I, $f, false);
    if ($f) {
        echo script("clearTimeout(timeout);");
        ob_flush();
        flush();
    }
    return $K;
}
function get_token()
{
    $hg = rand(1, 1e6);
    return ($hg ^ $_SESSION["token"]) . ":$hg";
}
function verify_token()
{
    [$Lh, $hg] = explode(":", $_POST["token"]);
    return ($hg ^ $_SESSION["token"]) == $Lh;
}
function lzw_decompress($Ea)
{
    $Pb = 256;
    $Fa = 8;
    $Ya = [];
    $tg = 0;
    $ug = 0;
    for ($t = 0; $t < strlen($Ea); $t++) {
        $tg = ($tg << 8) + ord($Ea[$t]);
        $ug += 8;
        if ($ug >= $Fa) {
            $ug -= $Fa;
            $Ya[] = $tg >> $ug;
            $tg &= (1 << $ug) - 1;
            $Pb++;
            if ($Pb >> $Fa) {
                $Fa++;
            }
        }
    }
    $Ob = range("\0", "\xFF");
    $K = "";
    $_i = "";
    foreach ($Ya as $t => $Xa) {
        $ec = $Ob[$Xa];
        if (!isset($ec)) {
            $ec = $_i . $_i[0];
        }
        $K .= $ec;
        if ($t) {
            $Ob[] = $_i . $ec[0];
        }
        $_i = $ec;
    }
    return $K;
}
function script($Vg, $Oh = "\n")
{
    return "<script" . nonce() . ">$Vg</script>$Oh";
}
function script_src($hi, $Hb = false)
{
    return "<script src='" .
        h($hi) .
        "'" .
        nonce() .
        ($Hb ? " defer" : "") .
        "></script>\n";
}
function nonce()
{
    return ' nonce="' . get_nonce() . '"';
}
function input_hidden($D, $Y = "")
{
    return "<input type='hidden' name='" . h($D) . "' value='" . h($Y) . "'>\n";
}
function input_token()
{
    return input_hidden("token", get_token());
}
function target_blank()
{
    return ' target="_blank" rel="noreferrer noopener"';
}
function h($gh)
{
    return str_replace(
        "\0",
        "&#0;",
        htmlspecialchars($gh, ENT_QUOTES, "utf-8"),
    );
}
function nl_br($gh)
{
    return str_replace("\n", "<br>", $gh);
}
function checkbox($D, $Y, $Sa, $Xd = "", $gf = "", $Wa = "", $Zd = "")
{
    $K =
        "<input type='checkbox' name='$D' value='" .
        h($Y) .
        "'" .
        ($Sa ? " checked" : "") .
        ($Zd ? " aria-labelledby='$Zd'" : "") .
        ">" .
        ($gf ? script("qsl('input').onclick = function () { $gf };", "") : "");
    return $Xd != "" || $Wa
        ? "<label" . ($Wa ? " class='$Wa'" : "") . ">$K" . h($Xd) . "</label>"
        : $K;
}
function optionlist($kf, $Gg = null, $li = false)
{
    $K = "";
    foreach ($kf as $Td => $W) {
        $lf = [$Td => $W];
        if (is_array($W)) {
            $K .= '<optgroup label="' . h($Td) . '">';
            $lf = $W;
        }
        foreach ($lf as $y => $X) {
            $K .=
                "<option" .
                ($li || is_string($y) ? ' value="' . h($y) . '"' : "") .
                ($Gg !== null &&
                ($li || is_string($y) ? (string) $y : $X) === $Gg
                    ? " selected"
                    : "") .
                ">" .
                h($X);
        }
        if (is_array($W)) {
            $K .= "</optgroup>";
        }
    }
    return $K;
}
function html_select($D, array $kf, $Y = "", $ff = "", $Zd = "")
{
    static $Xd = 0;
    $Yd = "";
    if (!$Zd && substr($kf[""], 0, 1) == "(") {
        $Xd++;
        $Zd = "label-$Xd";
        $Yd = "<option value='' id='$Zd'>" . h($kf[""]);
        unset($kf[""]);
    }
    return "<select name='" .
        h($D) .
        "'" .
        ($Zd ? " aria-labelledby='$Zd'" : "") .
        ">" .
        $Yd .
        optionlist($kf, $Y) .
        "</select>" .
        ($ff
            ? script("qsl('select').onchange = function () { $ff };", "")
            : "");
}
function html_radios($D, array $kf, $Y = "", $Kg = "")
{
    $K = "";
    foreach ($kf as $y => $X) {
        $K .=
            "<label><input type='radio' name='" .
            h($D) .
            "' value='" .
            h($y) .
            "'" .
            ($y == $Y ? " checked" : "") .
            ">" .
            h($X) .
            "</label>$Kg";
    }
    return $K;
}
function confirm($C = "", $Hg = "qsl('input')")
{
    return script(
        "$Hg.onclick = () => confirm('" .
            ($C ? js_escape($C) : "Are you sure?") .
            "');",
        "",
    );
}
function print_fieldset($u, $fe, $ui = false)
{
    echo "<fieldset><legend>",
        "<a href='#fieldset-$u'>$fe</a>",
        script("qsl('a').onclick = partial(toggle, 'fieldset-$u');", ""),
        "</legend>",
        "<div id='fieldset-$u'" . ($ui ? "" : " class='hidden'") . ">\n";
}
function bold($Ha, $Wa = "")
{
    return $Ha ? " class='active $Wa'" : ($Wa ? " class='$Wa'" : "");
}
function js_escape($gh)
{
    return addcslashes($gh, "\r\n'\\/");
}
function pagination($E, $xb)
{
    return " " .
        ($E == $xb
            ? $E + 1
            : '<a href="' .
                h(
                    remove_from_uri("page") .
                        ($E
                            ? "&page=$E" .
                                ($_GET["next"]
                                    ? "&next=" . urlencode($_GET["next"])
                                    : "")
                            : ""),
                ) .
                '">' .
                ($E + 1) .
                "</a>");
}
function hidden_fields(array $bg, array $xd = [], $Uf = "")
{
    $K = false;
    foreach ($bg as $y => $X) {
        if (!in_array($y, $xd)) {
            if (is_array($X)) {
                hidden_fields($X, [], $y);
            } else {
                $K = true;
                echo input_hidden($Uf ? $Uf . "[$y]" : $y, $X);
            }
        }
    }
    return $K;
}
function hidden_fields_get()
{
    echo sid() ? input_hidden(session_name(), session_id()) : "",
        SERVER !== null ? input_hidden(DRIVER, SERVER) : "",
        input_hidden("username", $_GET["username"]);
}
function enum_input($U, $ua, array $l, $Y, $ic = null)
{
    preg_match_all("~'((?:[^']|'')*)'~", $l["length"], $qe);
    $K =
        $ic !== null
            ? "<label><input type='$U'$ua value='$ic'" .
                ((is_array($Y)
                        ? in_array($ic, $Y)
                        : $Y === $ic)
                    ? " checked"
                    : "") .
                "><i>" .
                "empty" .
                "</i></label>"
            : "";
    foreach ($qe[1] as $t => $X) {
        $X = stripcslashes(str_replace("''", "'", $X));
        $Sa = is_array($Y) ? in_array($X, $Y) : $Y === $X;
        $K .=
            " <label><input type='$U'$ua value='" .
            h($X) .
            "'" .
            ($Sa ? " checked" : "") .
            ">" .
            h(adminer()->editVal($X, $l)) .
            "</label>";
    }
    return $K;
}
function input(array $l, $Y, $r, $ya = false)
{
    $D = h(bracket_escape($l["field"]));
    echo "<td class='function'>";
    if (is_array($Y) && !$r) {
        $Y = json_encode($Y, 128 | 64 | 256);
        $r = "json";
    }
    $sg = JUSH == "mssql" && $l["auto_increment"];
    if ($sg && !$_POST["save"]) {
        $r = null;
    }
    $Xc =
        (isset($_GET["select"]) || $sg ? ["orig" => "original"] : []) +
        adminer()->editFunctions($l);
    $Qb =
        stripos($l["default"], "GENERATED ALWAYS AS ") === 0
            ? " disabled=''"
            : "";
    $ua = " name='fields[$D]'$Qb" . ($ya ? " autofocus" : "");
    $oc = driver()->enumLength($l);
    if ($oc) {
        $l["type"] = "enum";
        $l["length"] = $oc;
    }
    echo driver()->unconvertFunction($l) . " ";
    $R = $_GET["edit"] ?: $_GET["select"];
    if ($l["type"] == "enum") {
        echo h($Xc[""]) . "<td>" . adminer()->editInput($R, $l, $ua, $Y);
    } else {
        $jd = in_array($r, $Xc) || isset($Xc[$r]);
        echo (count($Xc) > 1
            ? "<select name='function[$D]'$Qb>" .
                optionlist($Xc, $r === null || $jd ? $r : "") .
                "</select>" .
                on_help("event.target.value.replace(/^SQL\$/, '')", 1) .
                script("qsl('select').onchange = functionChange;", "")
            : h(reset($Xc))) . "<td>";
        $Fd = adminer()->editInput($R, $l, $ua, $Y);
        if ($Fd != "") {
            echo $Fd;
        } elseif (preg_match("~bool~", $l["type"])) {
            echo "<input type='hidden'$ua value='0'>" .
                "<input type='checkbox'" .
                (preg_match('~^(1|t|true|y|yes|on)$~i', $Y)
                    ? " checked='checked'"
                    : "") .
                "$ua value='1'>";
        } elseif ($l["type"] == "set") {
            preg_match_all("~'((?:[^']|'')*)'~", $l["length"], $qe);
            foreach ($qe[1] as $t => $X) {
                $X = stripcslashes(str_replace("''", "'", $X));
                $Sa = in_array($X, explode(",", $Y), true);
                echo " <label><input type='checkbox' name='fields[$D][$t]' value='" .
                    h($X) .
                    "'" .
                    ($Sa ? " checked" : "") .
                    ">" .
                    h(adminer()->editVal($X, $l)) .
                    "</label>";
            }
        } elseif (
            preg_match("~blob|bytea|raw|file~", $l["type"]) &&
            ini_bool("file_uploads")
        ) {
            echo "<input type='file' name='fields-$D'>";
        } elseif ($r == "json" || preg_match('~^jsonb?$~', $l["type"])) {
            echo "<textarea$ua cols='50' rows='12' class='jush-js'>" .
                h($Y) .
                "</textarea>";
        } elseif (
            ($Ah = preg_match("~text|lob|memo~i", $l["type"])) ||
            preg_match("~\n~", $Y)
        ) {
            if ($Ah && JUSH != "sqlite") {
                $ua .= " cols='50' rows='12'";
            } else {
                $M = min(12, substr_count($Y, "\n") + 1);
                $ua .= " cols='30' rows='$M'";
            }
            echo "<textarea$ua>" . h($Y) . "</textarea>";
        } else {
            $Xh = driver()->types();
            $xe =
                !preg_match("~int~", $l["type"]) &&
                preg_match('~^(\d+)(,(\d+))?$~', $l["length"], $B)
                    ? (preg_match("~binary~", $l["type"]) ? 2 : 1) * $B[1] +
                        ($B[3] ? 1 : 0) +
                        ($B[2] && !$l["unsigned"] ? 1 : 0)
                    : ($Xh[$l["type"]]
                        ? $Xh[$l["type"]] + ($l["unsigned"] ? 0 : 1)
                        : 0);
            if (
                JUSH == "sql" &&
                min_version(5.6) &&
                preg_match("~time~", $l["type"])
            ) {
                $xe += 7;
            }
            echo "<input" .
                ((!$jd || $r === "") &&
                preg_match("~(?<!o)int(?!er)~", $l["type"]) &&
                !preg_match("~\[\]~", $l["full_type"])
                    ? " type='number'"
                    : "") .
                " value='" .
                h($Y) .
                "'" .
                ($xe ? " data-maxlength='$xe'" : "") .
                (preg_match("~char|binary~", $l["type"]) && $xe > 20
                    ? " size='" . ($xe > 99 ? 60 : 40) . "'"
                    : "") .
                "$ua>";
        }
        echo adminer()->editHint($R, $l, $Y);
        $Lc = 0;
        foreach ($Xc as $y => $X) {
            if ($y === "" || !$X) {
                break;
            }
            $Lc++;
        }
        if ($Lc && count($Xc) > 1) {
            echo script("qsl('td').oninput = partial(skipOriginal, $Lc);");
        }
    }
}
function process_input(array $l)
{
    if (stripos($l["default"], "GENERATED ALWAYS AS ") === 0) {
        return;
    }
    $v = bracket_escape($l["field"]);
    $r = idx($_POST["function"], $v);
    $Y = $_POST["fields"][$v];
    if ($l["type"] == "enum" || driver()->enumLength($l)) {
        if ($Y == -1) {
            return false;
        }
        if ($Y == "") {
            return "NULL";
        }
    }
    if ($l["auto_increment"] && $Y == "") {
        return null;
    }
    if ($r == "orig") {
        return preg_match("~^CURRENT_TIMESTAMP~i", $l["on_update"])
            ? idf_escape($l["field"])
            : false;
    }
    if ($r == "NULL") {
        return "NULL";
    }
    if ($l["type"] == "set") {
        $Y = implode(",", (array) $Y);
    }
    if ($r == "json") {
        $r = "";
        $Y = json_decode($Y, true);
        if (!is_array($Y)) {
            return false;
        }
        return $Y;
    }
    if (
        preg_match("~blob|bytea|raw|file~", $l["type"]) &&
        ini_bool("file_uploads")
    ) {
        $Jc = get_file("fields-$v");
        if (!is_string($Jc)) {
            return false;
        }
        return driver()->quoteBinary($Jc);
    }
    return adminer()->processInput($l, $Y, $r);
}
function search_tables()
{
    $_GET["where"][0]["val"] = $_POST["query"];
    $Jg = "<ul>\n";
    foreach (table_status("", true) as $R => $S) {
        $D = adminer()->tableName($S);
        if (
            isset($S["Engine"]) &&
            $D != "" &&
            (!$_POST["tables"] || in_array($R, $_POST["tables"]))
        ) {
            $J = connection()->query(
                "SELECT" .
                    limit(
                        "1 FROM " . table($R),
                        " WHERE " .
                            implode(
                                " AND ",
                                adminer()->selectSearchProcess(fields($R), []),
                            ),
                        1,
                    ),
            );
            if (!$J || $J->fetch_row()) {
                $Xf =
                    "<a href='" .
                    h(
                        ME .
                            "select=" .
                            urlencode($R) .
                            "&where[0][op]=" .
                            urlencode($_GET["where"][0]["op"]) .
                            "&where[0][val]=" .
                            urlencode($_GET["where"][0]["val"]),
                    ) .
                    "'>$D</a>";
                echo "$Jg<li>" .
                    ($J ? $Xf : "<p class='error'>$Xf: " . error()) .
                    "\n";
                $Jg = "";
            }
        }
    }
    echo ($Jg ? "<p class='message'>" . "No tables." : "</ul>") . "\n";
}
function on_help($db, $Rg = 0)
{
    return script(
        "mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $db, $Rg) }, onmouseout: helpMouseout});",
        "",
    );
}
function edit_form($R, array $m, $L, $fi, $k = "")
{
    $ph = adminer()->tableName(table_status1($R, true));
    page_header($fi ? "Edit" : "Insert", $k, ["select" => [$R, $ph]], $ph);
    adminer()->editRowPrint($R, $m, $L, $fi);
    if ($L === false) {
        echo "<p class='error'>" . "No rows." . "\n";
        return;
    }
    echo "<form action='' method='post' enctype='multipart/form-data' id='form'>\n";
    if (!$m) {
        echo "<p class='error'>" .
            "You have no privileges to update this table." .
            "\n";
    } else {
        echo "<table class='layout'>" .
            script("qsl('table').onkeydown = editingKeydown;");
        $ya = !$_POST;
        foreach ($m as $D => $l) {
            echo "<tr><th>" . adminer()->fieldName($l);
            $j = idx($_GET["set"], bracket_escape($D));
            if ($j === null) {
                $j = $l["default"];
                if (
                    $l["type"] == "bit" &&
                    preg_match("~^b'([01]*)'\$~", $j, $qg)
                ) {
                    $j = $qg[1];
                }
                if (JUSH == "sql" && preg_match("~binary~", $l["type"])) {
                    $j = bin2hex($j);
                }
            }
            $Y =
                $L !== null
                    ? ($L[$D] != "" &&
                    JUSH == "sql" &&
                    preg_match("~enum|set~", $l["type"]) &&
                    is_array($L[$D])
                        ? implode(",", $L[$D])
                        : (is_bool($L[$D])
                            ? +$L[$D]
                            : $L[$D]))
                    : (!$fi && $l["auto_increment"]
                        ? ""
                        : (isset($_GET["select"])
                            ? false
                            : $j));
            if (!$_POST["save"] && is_string($Y)) {
                $Y = adminer()->editVal($Y, $l);
            }
            $r = $_POST["save"]
                ? idx($_POST["function"], $D, "")
                : ($fi && preg_match("~^CURRENT_TIMESTAMP~i", $l["on_update"])
                    ? "now"
                    : ($Y === false
                        ? null
                        : ($Y !== null
                            ? ""
                            : "NULL")));
            if (
                !$_POST &&
                !$fi &&
                $Y == $l["default"] &&
                preg_match("~^[\w.]+\(~", $Y)
            ) {
                $r = "SQL";
            }
            if (
                preg_match("~time~", $l["type"]) &&
                preg_match("~^CURRENT_TIMESTAMP~i", $Y)
            ) {
                $Y = "";
                $r = "now";
            }
            if ($l["type"] == "uuid" && $Y == "uuid()") {
                $Y = "";
                $r = "uuid";
            }
            if ($ya !== false) {
                $ya =
                    $l["auto_increment"] || $r == "now" || $r == "uuid"
                        ? null
                        : true;
            }
            input($l, $Y, $r, $ya);
            if ($ya) {
                $ya = false;
            }
            echo "\n";
        }
        if (!support("table") && !fields($R)) {
            echo "<tr>" .
                "<th><input name='field_keys[]'>" .
                script("qsl('input').oninput = fieldChange;") .
                "<td class='function'>" .
                html_select(
                    "field_funs[]",
                    adminer()->editFunctions([
                        "null" => isset($_GET["select"]),
                    ]),
                ) .
                "<td><input name='field_vals[]'>" .
                "\n";
        }
        echo "</table>\n";
    }
    echo "<p>\n";
    if ($m) {
        echo "<input type='submit' value='" . "Save" . "'>\n";
        if (!isset($_GET["select"])) {
            echo "<input type='submit' name='insert' value='" .
            ($fi ? "Save and continue edit" : "Save and insert next") .
            "' title='Ctrl+Shift+Enter'>\n",
                $fi
                    ? script(
                        "qsl('input').onclick = function () { return !ajaxForm(this.form, '" .
                            "Saving" .
                            "…', this); };",
                    )
                    : "";
        }
    }
    echo $fi
        ? "<input type='submit' name='delete' value='" .
            "Delete" .
            "'>" .
            confirm() .
            "\n"
        : "";
    if (isset($_GET["select"])) {
        hidden_fields([
            "check" => (array) $_POST["check"],
            "clone" => $_POST["clone"],
            "all" => $_POST["all"],
        ]);
    }
    echo input_hidden(
    "referer",
    isset($_POST["referer"]) ? $_POST["referer"] : $_SERVER["HTTP_REFERER"],
),
        input_hidden("save", 1),
        input_token(),
        "</form>\n";
}
function shorten_utf8($gh, $ge = 80, $kh = "")
{
    if (
        !preg_match(
            "(^(" . repeat_pattern("[\t\r\n -\x{10FFFF}]", $ge) . ")($)?)u",
            $gh,
            $B,
        )
    ) {
        preg_match(
            "(^(" . repeat_pattern("[\t\r\n -~]", $ge) . ")($)?)",
            $gh,
            $B,
        );
    }
    return h($B[1]) . $kh . (isset($B[2]) ? "" : "<i>…</i>");
}
function icon($ud, $D, $td, $Fh)
{
    return "<button type='submit' name='$D' title='" .
        h($Fh) .
        "' class='icon icon-$ud'><span>$td</span></button>";
}
if (isset($_GET["file"])) {
    if (substr(VERSION, -4) != "-dev") {
        if ($_SERVER["HTTP_IF_MODIFIED_SINCE"]) {
            header("HTTP/1.1 304 Not Modified");
            exit();
        }
        header(
            "Expires: " .
                gmdate("D, d M Y H:i:s", time() + 365 * 24 * 60 * 60) .
                " GMT",
        );
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: immutable");
    }
    @ini_set("zlib.output_compression", "1");
    if ($_GET["file"] == "default.css") {
        header("Content-Type: text/css; charset=utf-8");
        echo lzw_decompress(
            "h:M��h��g�б���\"P�i��m��cC���d<��f�a��:;NB�q�R;1Lf�9��u7%�d\\;3��A��`%�E�!���e9&���r4�M��A��v2�\r&:�Φs���0��*3�Má���-;�L�C@��i:dt3-8a�I\$ã��e���	��#9lT!Ѻ��>�e��\0�d��d�C��:6\\�c�A��rh��M4�k����Z|�O+��f�9��X�7h\"�Si����|�+9����ƣ���-4W�~�T:�zkH�b{���&�єt��:ܸ.K�v8#\",7!pp2��\0\\�� �\$�r7���ތ#��i\"�a�T�(L�2�#:\0Τ�x���XFǉ�d�&�jv����ڗ��@d�E�����!,9.+�`J�ahbDP<��|\"���Cp�>�ˑ+b2	L���{�F���Q�|����r��Kl�ɏ�_�t=���b�K|�������\r=�R�>����0��(���k�b�JU,PUumI.t�A-K��X�4�z�)MP��k���3e`�N>D#�9\\��(YT��@�h�L��1]ȴ��ͺNKն2\\73i/V��l��Y�җBA�/[J��ĘВ\r;'�2^텪�b�ۣ3�T=0H�8\r�+6��kf�C�]q��W)���²C��2`A���82�!��hm�вGD����2-C���Yc`�<�s��6�2���9�u���y��ҝMgy�=,CZO~^3���0�2�<��k0���wM�{d#`Zۀ����޺�����6�C%���=Rq���_+�-�K>��\n'G���A�\$����^�j><�gf�h�m�b*/\$\$l��ش�g)Aj�� w�#ᣃ�������TN��]�T���%Z���jJ����Cf4���zF�'�*� x�����ho&k\r��,��r�:>s(�LAs����5Ct���n�6��� ll\\9D��\\!��mv�\0�A{9`�.��סS�lg6���!.2��0�P�Ԡ�i\r\$7�w���;G��\$�0�CI�^�J\n�L�Pc��'�*Eh���b��;�p�B��(�x�:D�L�.j�9AC@�I3jf�5sI`X}��Ҕ#���7��T`d���jhH49S\nq��HJQ �H`,F��P�0\\{��m\r�~@20u!	\$�PoQ�4ǚ�\nZL�M��B��k)@�d��SL�pv���y�ЃB��^o���*�R�\"����#��rͥS;\r4�&G�I��T	�r���9=6���Q�T\0�\0��f#��=\$�������H6�P�Y�:�G\$�����0�9:a�3Hz;G�\r!hJ�n�7��	�oY������WLv�ې�i|���%�-��d\$�p�D�R2T\r�paU��n�5r��j�\$�r%D���)\$GڃBu��:��`�(l���SD)I	����9�*ꁗ\rt�2� �zI���g�[X�c,u�\rvJ5?����\"�:�^�.u�J�P�o\$t\$�18��\nnK��T%EZ,6��DH�V�󆪹i�&z��xpdr�x*�}ʐR�25+��ѓ�f�2�w��q�0X1�2dX�ߢ�̏W��ËV8f\"냐q(u��E��G�qM�#а�#K�3WA�v�Y���Þe�K]t��]E��j�=SX���@��Ӈ\r�Ә\$��9��ܬ0�P7\"D.匎<;��Nj�=�����^�mmڒG�68 �C�%v'�y��k/�^�5���3�@�.ڛ��k�a�*�D������:�7��C}��`�`�`)�7���|	�3� i�騽��4�\0.:�Q�L���؜ͨ��f�'�%�ݩM	���Y3�\0�##tP6�(�B�d����o�y�6�|�5��IH7�����z?�(��Ŗ\$�RWT�谦�:��(��`r϶i���s�=D\\��,kr�1��ٓ2�`��A�9��&n��~��Ҭ�6;�vp �M#�]����ɴ���A���iJ��.���t����Ys�OPwĸ�m��Z�����AU�ʷJ�N?����z�3\$P�qs�U9,�����#��5Pnb��uNѐ{N`�饙��۝�i��w\rb�&E�\\tg��b�a�1�+m�pw#�vl��U�����\0�e.��(�wb@����\\�w�(�)��E���;�Z�]/&���|>Q�\"c	<F�\r�7����ϵ\\�'�S�fe�\rR���Vlo/.��\n���F�o� �eh��e��jנ�T�sa4�2�2� �`o�\\�A?��]�IoB[�{7'��Q%�6�s7\$Ó�~%��u)�5i���0�#����\r�f���MX�N\\ɇ\0���,T���ETo\0�{o��R��r�^����C @Z.C,��c�'�J-�BL�\r�P�CD\"�b�^.\"�����h��\0�����\r\0���\n`�	 � ��n�o	����\r��\r��0�`���0��	��\rp� �	0�\n �F@�`� V\0�\n��\r\0�\n�j��\n@�\0�\r��\n��	 �\n@�@�\r\0�& �\n@� �@� ��z��Ƃ*��w�q0g�5�aPxG�����	�	��\n�\n�����\rp���\rp�\r��	��0�\r���`�\r@�@�� ���^\r �\0�\r�d@���3 ��1Q9�AB��<�t�1N?�S���v-�a��p��	p�P�\n����0������\0�@������Ѱ\r�^��\"i@�\n�� �6 �\0�	 p\n��\n��`� �qޒQD��B�M�d9�TUq�1���2�	�\n2�rR#��2+\r�/��#��@��\" �Q\r����\r����@�\n�h\n��〪���\0�`�	��@�!�;�Co�Uҋ2�����e Qk �p ���!P�3с!��r%���p�	 �,��`���\n�`\n@��ff ���`� �\n�@�	��F#�`p��# ���o���\\%Bl��?��M-jP��r��3/�3*Qlp�	p�\r`�=��\n\0_>�1�'�#\0�>\0�\r�������� �\n@��� f�0�'�@Ā�����\0�\rd�Fh�I\$�`���,����C�ȕPݔT�>�7\0]E̣ʉDG���AC�\\BMDԈ�fmd��(\r�OG�F��iDN��ɜn2�4tΔwFt��F�����H�CԈu+���\$K�6蔓E��.AKԏL*1JK>�����M��H��\"GN�Pj�E�>� �H&5H��LM#E�P�c��c8��l����,���C�N�Pt�@V t�\n���ݴ�I	kG�H	�)D(�JPl�1jn�lԍ��J�~�*&�n\\���HUfLk�K��r�F���<|HNx\\� Nl�N���I��\0rzMt�U|Z��ĸ����Ԙ�\r�HC�΀B\"�@�b�cn�A����J9Ort�A4�\r��@h��A^`�^�V0�^!L�jU����.�^\r\"���ka���lp��d� ���}\0��N�����IGP���U�YtyMPr��Y��E�ԥx����6`�`jg���1SB�܂�����X�8�V?Id[I��Q.�����`��i��̲vԞ��U)Ԝ���\n2PV�~�� �����\"\"&��r]-� �p*�\0f\"�Kj`�q\nJ�\"q��F�.��\"@r��(�`��3q>\"��f��\r\$ح�� �R1��h&H�`Z�V	u+Moʬ�\n3J�\r Đ�2I� �D'�!S0W0J?y�pjZ.�\n\r ��pw�\"�-+�zr!`�|v2\nl�f(�m�<��=�F\r�Q}��~7��\r��#�o�3��}���x<�~��W���iE�ã��[�8\n�bjj�\r��: ���)v��'{��V�q\no{���)C����߂�i���\r�%�遀���C�(��k�k������4ؐd�� ������jXLN�(A�}xe���|��w���G��xh��X�x�\r��%K���޼oq�x�������8s�4e���xρ��n�*4F�c�8~�h�Ip]��{���%�( �Ӓ��<�V����C�B��{w����O��ƣ��}�Q�8�[���[�{�cT%�&��o�����:�*b�E�`�m�I�YW�k�8��Yo������u�)����Y5o�9��ަ���ى��<�8(�?�\0[s�@*8���}��ߟ9g�\rӟ������\0���\n'w±x)݌٩�����92�Z1��@[I�+��_��5�7=��D�q�z!}�K��Nd��3�\0��q�+��C����Y_�g�8��y����ډ�K�4�{��S�8�2Z��z��X\0Ϩz���S���ڱ��e� �\r�>�:������Ǭ�_�ZÏ��e�������:�����u���{�U�M���a�����B��zɈ���b2YS�WJ(wOwӁwm��تZN�l��˧C��9������8BD��6���Zy�x{���;!�[m�����{}��)��#�4�[���(�b���ɘ���Ն�u��������,O�\"F�7�y?�9���nd�}�����{ݍs�{���e�ʦ>\"�cc����d���cs�{��vdCN�[���GM�C瓭�DE@",
        );
    } elseif ($_GET["file"] == "dark.css") {
        header("Content-Type: text/css; charset=utf-8");
        echo lzw_decompress(
            "h:M��h��g���h0�LЁ�d91�S!��	�F�!��v}0��f�a��	G2�Na�'3I��d�K%�	��m(\r&�X��o;NB���y>2S�*�^#��Q���1=���J��W^��L�����o����c!��f��6�m�a���l4&1Lf�9��u7VDc3�n82IΆ��,:5���r�P��1��m�>�5��W/��Fc���Dh2�L�\rN����Wo&�hk��e2ٌ��b12Ƽ~0� ��D}N�\0�f4�M�C�����n=��p��Zش�N�~�;���-C ��%�z�99�P���\"����;�\0f��8�9�p�:�m�8��@\nX�:��r�3#����C�[�Cx�#��I2\\�\"��p����]#��5R�r6�#�L7���!H�\$�\$IRd�'ˉ8]	��x��+�>�C�@-��;���b�<�2Ô�N4,�����-Mr�6Ic�X4�a���5KE�Th@1@���R�K�9\r�(�9�#8�G�CpwID5�2�l\"_'��UB��U�9c@�G=C\n��S��0���j��7PU�����9J]�<׋\nƲσz�?B��2�����4\r/�P\r�M[X��F�_��j���H�bnC&�¡f%@cC^.2�8�׎C�}^��sw�L��/�5O�M��ڳ	*X�?�b�.I�g��&�aq�݊>���FN�-�`�y��4�s���j\\&:�Sa�P;����H�����X��ޯ���d�kt?.���,ZO��@@8�Z3�c�\"��ß\n�=A�H1\\�Z�^/k���΃L�uC\\�c�)0O��M��lpr��7�\r��q�����WRa�������c@���wm�k/�8�*?�̐�4�5�\\m���k��>�d1n��UQ#�����w�憟�Lo&hĪPrnR,5����z�\"\$3��dYH(p\r�ALAC�)pT�Pl�!\"L��8��R�&�\0�����Z౒0P8������J	�`��e�0�	����1��	�D��Js�H���)�k� ��[���C�y�pjx,\rA���m!���<h1�",
        );
    } elseif ($_GET["file"] == "functions.js") {
        header("Content-Type: text/javascript; charset=utf-8");
        echo lzw_decompress(
            "':�̢���i1��1��	4������Q6a&��:OAI��e:NF�D|�!���Cy��m2��\"���r<�̱���/C�#����:DbqSe�J�˦Cܺ\n\n��ǱS\rZ��H\$RAܞS+XKvtd�g:��6��EvXŞ�j��mҩej�2�M�����B��&ʮ�L�C�3���Q0�L��-x�\n��D���yNa�Pn:�����s��͐�(�cL��/���(�5{���Qy4��g-�����i4ڃf��(��bU���k��o7�&�ä�*ACb����`.����\r����������\n��Ch�<\r)`�إ`�7�Cʒ���Z���X�<�Q�1X���@�0dp9EQ�f����F�\r��!���(h��)��\np'#Č��H�(i*�r��&<#��7K��~�# ��A:N6�����l�,�\r��JP�3�!@�2>Cr���h�N��]�(a0M3�2��6��U��E2'!<��#3R�<�����X���CH�7�#n�+��a\$!��2��P�0�.�wd�r:Y����E��!]�<��j��@�\\�pl�_\r�Z���ғ�TͩZ�s�3\"�~9���j��P�)Q�YbݕD�Yc��`��z�c��Ѩ��'�#t�BOh�*2��<ŒO�fg-Z����#��8a�^��+r2b��\\��~0�������W����n��p!#�`��Z��6�1�2��@�ky��9\r��B3�pޅ�6��<�!p�G�9�n�o�6s��#F�3���bA��6�9���Z�#��6��%?�s��\"��|؂�)�b�Jc\r����N�s��ih8����ݟ�:�;��H�ތ�u�I5�@�1��A�PaH^\$H�v��@ÛL~���b9�'�����S?P�-���0�C�\nR�m�4���ȓ:���Ը�2��4��h(k\njI��6\"�EY�#��W�r�\r��G8�@t���Xԓ��BS\nc0�k�C I\rʰ<u`A!�)��2��C�\0=��� ���P�1�ӢK!�!��p�Is�,6�d���i1+����k���<��^�	�\n��20�Fԉ_\$�)f\0��C8E^��/3W!א)�u�*���&\$�2�Y\n�]��Ek�DV�\$�J���xTse!�RY� R��`=L���ޫ\nl_.!�V!�\r\nH�k��\$א`{1	|�����i<jRrPTG|��w�4b�\r���4d�,�E��6���<�h[N�q@Oi�>'ѩ\r����;�]#��}�0�ASI�Jd�A/Q����⸵�@t\r�UG��_G�<��<y-I�z򄤝�\"�P��B\0������q`��vA��a̡J�R�ʮ)��JB.�T��L��y����Cpp�\0(7�cYY�a��M��1�em4�c��r��S)o����p�C!I���Sb�0m��(d�EH����߳�X���/���P���y�X��85��\$+�֖���gd�����y��ϝ�J��� �lE��ur�,dCX�}e������m�]��2�̽�(-z����Z��;I��\\�) ,�\n�>�)����\rVS\njx*w`ⴷSFi��d��,���Z�JFM}Њ ��\\Z�P��`�z�Z�E]�d��ɟO�cmԁ]� ������%�\"w4��\n\$��zV�SQD�:�6���G�wM��S0B�-s��)�Z��cǁ2��δA;��n�Wz/A�Zh�G~�c�c%�[�D�&l�FR�77|�I���3��g0�L���a��c�0RJ�2��%���F� S� �L�^� tr���t����ʩ;��.喚Ł�>����[�a�N���^�(!g�@1����N�z�<b�ݖ�����O,��Cu��D�tj޹I;)�݀�\nn�c��Ȃ�W<s�	�\0�hN�P�9��{ue��ut뵕������3��=��g�����J����WQ�0���w9p-���	�������'5��\nO��e)M�)_k�z\0V�����;j�l��\n����x�Pf�-�`C�.@&]#\0ڶp�y͖ƛ�t�d�� ��b}�	G1�m�ru���*�_�xD�3�q��B�sQ��u��s%�\n�5s�ut���{�s�y���N��4�,J{4@��\0��P���^��=��l���`�e~F١h3o�\"��q�R<iUT�[Q��U��M�6�T. ��0'�pe\\�����5����pCe	ٕ�\"*�M	����D���?�h��2���zU�@7�C�4�a��iE!f�\$�B��<�9o*\$��lH�\$ �@����P\rN�Y�n<\$�	�Q�=�F&��*@]\0��� W'd� z\$��j�P[��\$���0#&��_�`+�B)�w�v%	����LcJ��RS��i`�Ů	�F�W	��\nBP\n�\r\0}	瑩0�Z���/`j\$�: �8ie���φx�����a ���Gn�sgO��U%VU��@�N��ϐ�d+�(oJ�@X���zM'F٣�WhV�I^٢�1>�@�\"���� ��Q�R!�\\�`[������.�0fb�F;���Fp�p/t`����(��V���b�Ȳ�(��H�l����ԯ1v�����H��1T�3�q���1�Ѫf�\nT\$���Nq+��`ލv�ǜ�\r�Vm���r���'ϸ��g%�\"L�m����(�(CLz��\"h�X�m=�\\H\n0U�� f&M\$�g\$�U`a\rP�>`�#g��h��`�R4H��'�����GK;\"M�ۨT�h�BE�n\"b>���\r���#�\0�N:�#_	QQ1{	f:B���R�&���)J��Br�+�K.\$�Pq�-r�S%TIT&Q���{#2o(*P��5�`�1H���'	<T�d����s��,N�� ����^\r%�3��\r&��4�B�/\0�kLH\$�4d�>���/�ඵ�H���*���3J�А�<�Hh��p�'��O/&�2I.�x3V.�s5�e3��ێZ�(�9E�g�;R�;�J��Q�@��vgz@������'dZ&�,U���F��b*�D��H! �\r�;%�x'G#��͠w��#�֠�2;#�Bv�X��a�\nb�{4K�G��%���GuE`\\\rB\r\0�-mW\rM\"��#E�cFbF�nz���@4J��[\$��%2V��%�&T�V��d�4hemN�-;Eľ%E�E�r�<\"@�F�P�L �߭�4E����z`�u�7�N�4��\0�F:h�K�h/:�\"�M�Z��\r+P4\r?��S��O;B��0\$FCEp��M\"�%H4D�|��LN�FtE��g���5�=J\r\"��޼5��4�K�P\rbZ�\r\"pEQ'DwK�W0��g'�l\"h�QF�C,�Cc���IH�P�hF]5�& f�T��iSTUS�����[4�[u�Ne�\$o�K��O ��b\" 5�\0�D�)E�%\"�]��/���ЌJ�6U�d��`��a)V-0��DӔbM�)���������`��%�ELt��+��6C7j�d��:�V4ơ3� -�R\rG�IT��#�<4-CgCP{V�\$'����g��R@�'��S=%���F�k:��k��9����e]aO��G9�;��-6��8W��*�x\"U��YlB���������	��\n��p���l����Z�m\0�5����Oq̨��b�W1s@��K�-p���E�Spw\nGWoQ�qG}vp�w}q��q�\\�7�RZ�@��t��t�;pG}w׀/%\"L�E\0t�h�)�\r��J�\\W@�	�|D#S��ƃV��R�z�2���v�����	�}�����(�\0y<�X\r��x���q�<��Isk1S�-Q4Yq8�#��v���d.ֹS;q�!,'(���<.�J7H�\"��.����u�����#�Q�\re�r�Xv[�h\$�{-�Y���JBg��iM8��'�\nƘtDZ~/�b���8��\$��DbR�O�O��`O5S>����[�D�ꔸ����_3X�)��'��Jd\r�X����UD�U�X8�x�-旅�P�N`�	�\n�Z���@Ra48��:���\0�x���N�\\�0%��f��\\��>\"@^\0Zx�Z�\0ZaBr#�X��\r��{��˕�flFb\0[�ވ\0[�6���	��� �=��\n��WB��\$'�kG�(\$y�e9�(8�& h��Rܔ��o�ȼ Ǉ���Y��4��7_��d��9�'���������z\r���  ����v�G��O8���MOh'��X�S0�\0\0�	��9�s?���I�MY�8� 9����HO��,4	��xs��P�*G����c8��Qɠ��wB|�z	@�	���9c�K��QG�bFj�X��oS�\$��dFHĂP�@ѧ<嶴�,�}�m��r��\"�'k�`��c�x��e�C��C��:���:X� �T���^�d�Æqh��s���Lv�Ү0\r,4�\r_v�L�j�jM��b[  ��ls���Z�@�����;f��`2Yc�e�'�Mer��F\$�!��\n��	*0\r�AN�LP��jٓ����;ƣV�Q|(��3����[p��8���|�^\r�Bf/�D���Ҟ B��_�N5M�� \$�\naZЦ���~�Ule�rŧr��Z�aZ�����գs8R�G�Z��w���N�_Ʊ�Yϣ�m����]��;ƚL�����c������Ű��I�Q3��O��|�y*`� �5��4�;&v8�#�R�8+`X�bV�6�ƫi�3F��E���oc82�M�\"����G�Wb\rO�C�Vd�ӭ�w\\�ͯ*cSi�Qү��R`�d7}	���)�ϴ�,�+bd�۹�FN�3��L\\��eRn\$&\\r��+d��]O5kq,&\"D�CU6j�p���\\'�@o�~�5N=�|�&�!��B�w�H�yyz7��(Ǎ���b5(3փ_\0`z�b�Уr��8	�Z�v�8L˓�)��S�M<�*7\$��\rR�b���B%��ƴDs�z�R>[�Q����&Q������'\r�pp�z�/<��}L�#��Ε���Z��\"t��\n��.4�g�P��p�D�n�ʹN��F�d\0`^����\rnȂ׳#_�� w(�2�<7-��X޹\0��s��,^�hC,�!:�\rK��.��Ӣ�Ţ���\\��+v�Z��\0�Q9eʛ˞E�w?>�\$}��D#���c�0MV3�%Y���\r��tj5��7��{ŝ�Lz=�<��8I�M�����G����L�\$��2��{(�pe?u�,R�d*X�4�����\0\"@���}<.@��	��N��\$�XU�js�/��<>\"* �#\$����&CPI	��t������?� ��	�O��\\��_��Q5Y�H@���b��c�h����뱖��O0T�'�8�w�����j+H�v_#�����06�w֎�X��d+�ܓ\\��\n\0	\\�>s��A	PF�d8m'@�\nH�\0�c�OwS�����Y�`�����R��Dna\"��~�?�m���|@6��+�GxV��\0��W�Ӱ�nw���.�؃b��9Í��E�|E���\rЈr�\"��x���-���\rN6�n�\$Ҭ�-B�H�^�)��y&��ךW�ǧ�bv�R�	���N\0��n�	T��`8X��A\r:{O�@\" �!��\$K�qo��jY֪J�����h}d<1I�xd����TT4NeeC0䥿�:D�F�5L�*::H�jZ��F�R�MրnS\n>PO�[�\$V8;#�K\\'�B��R�د��R�_�8�j��*Ej�\\~v���v��p@T�X�\0002dE	�H�V���D�\"Q'EDJB~A��A�Il*'\n�Y��.�+�9��pg���/�\"�1�8�0�IA�FCȨ�V*a��P�d�У5H\"�A��6�s�Y��;訞�/��0��v}y�\r����ץ1�u\"ˋ�m��_�0焄`���\\B1^\nk\r]lh�}]HBW`��0�꨹rFf�)�W,�ҧ]sm9'O�xԽ�,�9J8��?�4�����\"҅�۽�<�-S����M�;�v��6y|�Z����%�a�#8��TC�!�p��\n��CZ(�w��a������?9|��0<BL\r�\n�]�PB0�&�+t�H���օ�Dx^��,�L�}[��B�x}��ru��\0��\0005��S@\"Uؔ@��\0�\$��ސ\"Ҡ��]l/	��I�B4��.�6���d7��\r@=���߬���*G j����f`��:Hn��bĀ71��)C<@A�Y#�����e�o��Y!��I�DM�\nlt����/)�\\43)��2��ɸ�)���f[ ppp1���#��Ð�p\0��œl��^{��A��TH�6�����\n\0P�H�.\r���|�T�FD0��S�y����'1���K���d�����B���C�&�)�W�s Hee+@4� r���ۚ*Lp1<�f�N�Y'�-	XKVa��L���\"���\"�l��q��.YJH�m HV�/�lC�&��H)o�&\\2���%���z\n^Q(6�D� ����Jq���\00a#�6\0vr,�M��&A�������9%Yd��B�h��!W\0�b\r{���@�1��I�22�A��)�H�a@r�0G��7Dd.�LM�<��2���,k/��Me����}Ғ3�=\0�&��B��\nPd.\"��F3X��Sd(*�J6 ���F:��)1�1�?lQ&����h<J͋�f�d�Eպ*�x\n\0��.\"B -�#��Ηt�IΫ���	I8 ��8dh	��x���~��	L!K(�BX��-��h��c/�r��P�I���N�2�|��׶��|\"�M�'��K,\\H��e5*o]4��FP	2��<)�T���o��\n���I�ڢ�!�(���_8Xr�;u�����NJ�����[r��DC:�@�ͳ�l�\0�e\\*x@Aȡ&�(�5��,����#1x� �!T�D���(Q���DJ|D D:\0�A�й� �baE�?rn��Wkx��X=i��,\$3�[�r�9B�Ʊ�d��\0��H��4���<(z���?�sIbJ�g U�\n(}���J\"��A��B�19�~�I�#�\$��%d  e\"�`���t���'O=���@\$��O�\nmT�o+�Z����-�����PF?�_�I�J�X ģ2���-V�;�?2���0�*P3����_T<E�J�\\(�2����)�IQ���鬩���R��L&��!ȯK�iц�t����K�HRl�ȬEs�������D��xǴ�i���!faB���F��e>�V����-Qj�I��7���\"%Rh� g��M������-�b�58R����*��9��ꊰ���9�2Q0���IR[�Z��N\0���20�����\\[@�Q\0��Jx�����EC{���\$lp1=\0�Rо�>E~�������:0���%��R+)\0�	ƑQ�@(\"�_j�T�X\0����\r1�\0P�9#\0����H;B�|���L�Z�����6�/B��\nB�{���|H�,�	*;��(�`�2@6�>�	�?P\0/���\0|\\�eB�`��jq�U/\rc�����҆�6(N\0�/\$�\n8�j*U�\$��y*�=�;���\$�f��8X�BCE��r\"/�����kځ%\\9k���B���0�F��(��'�U���Ʈm�@k�T\0��E��sEhy�e\n�)�)��b7��(W%,�J�r��2D�rhE��\n0Q�3� U�9TPO������8j|�}�R<0���Zl ��T�������*�\$��U\r�\"�.� Ts~�~(�3�a���@��+���l�`:�`�:O�i��BX�?ʄ��7��Lj|�:n�K:ز}�\0��UMc`P%nn\n,�4�Q'%+H.�\"#G��3`�����\n1fg\0�М'�k��qxD<\"��,a|{~���C<S�i�B�\nkN���G�}���k:��������g�)�JD���hÛf�\"�kV~��mM`HO�kD��^�0/tj�l�\r�!�f<��G��T���v�#@�ek@2�w���0�ܭt���į1�u�yvː%8�?1���l��xt��mp��fK3Z�J�=\0@�^p��ۑ����]Ҳ'�t١@C�b��\r[��V��-���o�-��ݠe�}��Y��	-�-m�I\0+��V�D�[B+��(�-�4�>�q��i>=��/0-�cL�pJ b\nd��)�#��G�s����\"�Q�N����`.�ȍ�yȐEtP�q�I]��J8���rWT��I���f�aG�.떄7y��l��A��7'�1�	�S�-�xI��m���L:e�ΉA�W��ζEI��Wz��3W���)*/)C���x*c]�%�}����_��IvͲ�'�\$U��S4k�5WʏJC���7*�b%<WC@�	����c{޴���3)X�&&��eL�I���,N� 2k#p5���f4���Ǻ�z�#��\\����N�b�U��oy���S�4�`q�~1�=�8厉�*�OOJ�C�����'Dd,@kL�������\\�j2ͩ����<�@_q�2�\0�ձ�)`�������s���F\0�����\n���F��<*�x*����`����-��\r���|@����7�H@w����H]��\0�����_w��h0!�s�1Ϗ��Ǭ�hW��.��=W��R*�A_���EDԷ�?1,Ub�9=t�4è��W��^���;����@��(1<D�ÊHx�T()0z�`�_�;��AL��)\n�K[f�H���Wo�@bBK�iM���d+�>�vI�(z:��.݀��9uiѤDY����O`���]I\0��R�Ć,K,���6L��\"\"�1g�(���|T.,�9vb+\rk]u�&�|��b�S��d[�,g��aJ�(C��k��\rF�+	��9��L��))UA�B�U�h�g��c3x�-n9�����x��2��q�ib�rY7�k�y�f�,������)�٪�J:�N�8�Rcly\n��2�W�;�.>�v6Q#A0��{έi��7~@VX���^��11-�+�v|��]Vf���.�{	���\r��;�1lp�/��uF��d�\$PЮ0=@kS�0h��Ɉ@��/*(O�V.��G>�(r��!�6�����Y=XZ@�:�'&0�6kE|���'|H;���N�g�%�W�+��4�;̓��'x|�f�9���(O��d���w%9]��f}��G���s���¾�����XM0����gQ���8̄�+O}�͝0}�9�������Nh�/mgD���s������\n�74勳P~}O)�Ug�9���j�8P��ݸ�(�%����j�7oAB��i)��K��u�� �}s�1�=od�V[Ĵ\n��zl�Mзr:F#{��*#�x��ܰ�<Ds��k/mw :^����1��ύD��2�z*��n��%�����i�Ù *�!8-��tH�'����\r�к�4����8`��\"�����i]�ZZ�>Z\0ަ9����+䟂~��\$ޭ��L�P\\쇁�XA�������i���z�h�\$�SM�T'���1���D��	��5E�\0Ğ\$�ttԮ��:\rMƷS��Ӗ�ls��Af�K�k,N�l�D^zz�dS��/rt�N�>��o%i��\0J�B�po��R����/֘٫x\ny�+��,e4��q5Q'JD�]�B@m����R�Ski~����t0�[ 1�z	���&��^�\nO����V����GV@T*�H9�ωG0\0'�`�Ѱ\r���bQKsLd�*;\n����.ĔUNp�,L�@TR�e��b��F���y�n> IK��rG�	@��?cI�ݓu%G�O�1���C�h�5T�y��I��:\\0��X��>�ʊ�0�޾�QB���EI/-LBT�!b��6���k`jp\0K���>k�d���/���ISk.+*���R�|gR���W\\w���t�.)�^Zc8�Z�~F��Sǵ�S�m̕;b>\0jz=�T'�>��q�y}:�u��&��W�DQ��c-����6<[��e�x�ؠ���[���L�\0wm�l�t�z��<S�&��db�x��oi�gK�\r`�µ�?D5u@b���N��O�𤷤���Y�[�����{�Nr鉞�t���\0��tMs�cBW?�*D�.p���'2��Ge\rp*#�e�����C���\"�QI\n��hi�Q�@���\rl	����_.���t*�^��s�9���Whq���~,��Yθ��dQs¦\r�Bj��D�ǡ��<<T)C�\n�����&�D{\r�l���-R��\r@rk��Ϣ��+Z���P������u8Ȩ����s�و���o�#��g��u\$F�&\n-v\"P����j�nnt�1��V������Awbx߄�D�5��-�0�a�\0\r�/!�I����|/����h��n�Gf-Mdna�^(e�a��¨�Y��Z,�S�E�N��\\�����=�4~Mʹ�\r����Ft�Ŧ�u\"|`��E��R�z��D�`�{��@�k/K�Y����3sJ�䃿5XGͪ�%�9)Q�� �Q���1t�h��!TR���H���Q�\r�C��E�0�#w�G2��/���/��=^ �/Ժ�ΐ����E��\0{+���t�+��q�б��I�t�|����v��q��Ԉƌ&�\r\\�Vߠ=���Eb��nO�rn��X({�ɹuzK��`=:�\n����\0����[�%�:p���q+��R�ldY��\"��[V�u{H-��H�_��8j��V��5����\"\0\"N?E;+�O~�wN�];L�'���SOF����䁻��D�-�!#sN�<��� ¯��mu����G�8���Tn]�����:�zIMn� O�8���z5���o\\5�7�<��Ų#8���?sN�L��	}�x��&4�?�[�z���󳷶����<*W������e}{HZ���,(<o�o�xW�t�2���#�A*�����o\\�R�}xH>NP�|Qɚ|x�'�-� ��2\0��?ƾ2*\r|]t��p�\"�ڲJuuXyb�D\n�Z|�H7�_�W���GuXyH>T\r�G����Ql�������n!�u'�*�C5��>U�2!b	��9Pw��4�����}y�W�|���a\$�g������T�U��&~9(\\*�!b_����w�7\\����]=�\\*���@�#N7ͪ��5QN`@<\0�6!�9��l��\$�wI\$4���2��\$�&���.RZ����Y��uyᤳ�p�&SI��@�EJiL�c���V�1F�1��Z\r\r���h��k���HH��˿�����K���?x��-0\n��d�N3K��C�59)ľ:B#���dN5A1�Ɖ����Od[3ڠ��h�[s~)�9�DN�y����>���X��'Ƚ�ϐH���,��)ڂ�\"�e�0;\0�qeo>��=�|�2�G+B�@z�������@]}��rQ��� k/�|�G�:ѯ�W\0�a4>��^|���g�o�XE�9p���Lrg�A��6��p�e����1�*����7��[�>]�#�?jB�~�/�}�3�:��U\$�?�<��G��a���\n>0#!i�>.{A}'hQ�Lw�~�W_��Th#d��û��d��FQ�����*{��\"�\"�P{���}�4�N���i��\r_����e?l4�2�?\n�F��	��q�U��Ľ�_��`_�����j��{_k_�o�~��c*#�(�/�!Dn�F�`��?@s�B�!�?;�E��������\0k�	�*N��D;���+d\nZZdB��� ��`B5�P\n8�������c#ou��k�ˊM�ݯw�.��F�J���!|�Ĉ2Fc�Y).����XHy�[��~����#/�&����[�����Y@���(|\r\0,O��0Yb��βŬ�\$0���aˑ����� �A\$��0,�@�Ӱ>>9��\\t�i�<�\0�q\0�}@`�\0fVj����dߠ'(����	!_�n��0+c���iig8a]'=-�B!(��8�_���x�j�����)\rH5H�Yn	,f�r��}-d\$��H��2n鴆ܛ�=�-�d���FE-d��a��N_z4@��[�n��\$x!!i0T����u�8�ɸ����\0PZ8Z����c����+Њ�AAF(����`mg*�vS, ǆ��KcA�۬ &��9������c�0w�+�n��=��)\$���Q�~A��a�\0004\0u�{�(��\$���y	!��B�� A<�a��Az ���ZA4\$ZY9.aX\r��d�A�L�v|oOz|�Z�(�e�Z�Ć�",
        );
    } elseif ($_GET["file"] == "jush.js") {
        header("Content-Type: text/javascript; charset=utf-8");
        echo lzw_decompress(
            "v0��F����==��FS	��_6MƳ���r:�E�CI��o:�C��Xc��\r�؄J(:=�E���a28�x�?�'�i�SANN���xs�NB��Vl0���S	��Ul�(D|҄��P��>�E�㩶yHch��-3Eb�� �b��pE�p�9.����~\n�?Kb�iw|�`��d.�x8EN��!��2��3���\r���Y���y6GFmY�8o7\n\r�0�<d4�E'�\n#�\r���.�C!�^t�(��bqH��.���s���2�N�q٤�9��#{�c�����3nӸ2��r�:<�+�9�CȨ���\n<�\r`��/b�\\���!�H�2SڙF#8Ј�I�78�K��*ں�!���鎑��+��:+���&�2|�:��9���:��N���pA/#�� �0D�\\�'�1����2�a@��+J�.�c,�����1��@^.B��ь�`OK=�`B��P�6����>(�eK%! ^!Ϭ�B��HS�s8^9�3�O1��.Xj+���M	#+�F�:�7�S�\$0�V(�FQ�\r!I��*�X�/̊���67=�۪X3݆؇���^��gf#W��g��8ߋ�h�7��E�k\r�ŹG�)��t�We4�V؝����&7�\0R��N!0�1W���y�CP��!��i|�gn��.\r�0�9�Aݸ���۶�^�8v�l\"�b�|�yHY�2�9�0�߅�.��:y���6�:�ؿ�n�\0Q�7��bk�<\0��湸�-�B�{��;�����W����&�/n�w��2A׵�����A�0yu)���kLƹtk�\0�;�d�=%m.��ŏc5�f���*�@4�� ���c�Ƹ܆|�\"맳�h�\\�f�P�N��q����s�f�~P��pHp\n~���>T_��QOQ�\$�V��S�pn1�ʚ��}=���L��Jeuc�����aA|;��ȓN��-��Z�@R��ͳ� �	��.��2�����`RE���^iP1&��ވ(���\$�C�Y�5�؃��axh@��=Ʋ�+>`��ע���\r!�b���r��2p�(=����!�es�X4G�Hhc �M�S.��|YjH��zB�SV��0�j�\nf\r�����D�o��%��\\1���MI`(�:�!�-�3=0������S���gW�e5��z�(h��d�r�ӫ�Ki�@Y.�����\$@�s�ѱEI&��Df�SR}��rڽ?�x\"�@ng����PI\\U��<�5X\"E0��t8��Y�=�`=��>�Q�4B�k���+p`�(8/N�qSK�r����i�O*[J��RJY�&u���7������#�>���Xû�?AP���CD�D���\$�����Y��<���X[�d�d��:��a\$�����Π��W�/ɂ�!+eYIw=9���i�;q\r\n���1��x�0]Q�<�zI9~W��9RD�KI6��L���C�z�\"0NW�WzH4��x�g�ת�x&�F�aӃ��\\�x��=�^ԓ���KH��x��ٓ0�EÝ҂ɚ�X�k,��R���~	��̛�Ny��Sz���6\0D	���؏�hs|.��=I�x}/�uN���'R���n'�|so8r��t����a�\0�5�P�֠dẘ��̕q����5(X�Hp|K�2`�]FU�~!��=� �|�,up�\\���C�o�T�e╙C�}*��f�#�shp��5����mZ�x��fn~v)DH4�e��v��V��by�T��̥,���<�y,̫֞�2���z^����K��2�xo	� ���2� I��a�h�~��c�ej�6��)�]����5�͍dG׊E�t�'N�=V��ɜ@����b^����p:k��1�StTԙ�F�F��`��`��{{���4��7�pcP�ط��V��9�ىLt�	M�����{�C�l��n47s�PL��!�9{l a�������!pG%��)�<��2*�<�9rV���\\����]�W�tn\r<ė�0�vJ�栱I�i��1��Ys{uHհ?�ۖ�����U�o�Aߒr`S��Cc���v�˳J�c�����=��-H/���q'E���w|��N�{\r};��>�x�r���u5�B�*\0����M�����a��\0�{HU����C�W�廳�yB'�<�6�[��s���y����@��{�Q����>?/<�K@� ���B�|aH\"�� R	�@>~@�BhE�L\$�[��Sa \"�Ђ0�Fe`b\0����@�\n`�=���n��.*̔�O��Ϙ���n��<jO�lM�\"mR���/��*�&T肙�T� _E4����8���|R0*�o��Bo>S%\$�� �N�<�|��ξ����y�7\n����޴�,鯢������쐬�Pt��\"l&T�o��E05n����o��r��v�������֣Bp��p��\n���P���.-,��q����3\r/�p��P���b����%m���P2?P����@��0(�/gpz�0�`��gυ��ϑ�\\嬳q��>��p�@\\��u��@���\$Ne�Q������0(A(�mc�L'`Bh\r-�!�b`��k`�������`N�0�	�ЯnN�`��D\0�@~����`K���] �\r�|���ʾ�A#��i�Y�xf�\r�4 ,v�\0ދQ�ɠN��Xo������ q�'��t�r\$��np�6%�%lyMb�ʕ(�S)L')��ޯL�M�I�s {&� KH�@d�l�wf0��x��6��~3�X�h0\"��D�+�A�\$��`b�\$��%2V�L�� Q\"��%���R�FV�Ny+F\n��	 �%fz���+1Z���Mɾ�R�%@ڝ6\"�bN�5.��\0�W���d��4�'l|9.#`��e憀�أj6�Τ�v����vڥ�\rh\r�s7��\"@�\\DŰi8cq8Ğ	�\0ֶbL. �\rdTb@E � \\2`P( B'�����0��/��|�3����&R.Ss+-���cAi4K�}�:�����\0O9,�B��@�CC��A'B@N=�;��7S�<3�DI��MW7��ED�\rŨ�v��@�DȺ�9 �l~\r�d���5�z^�r!�}I������sB��\0eT�K�!�K�UH���/����2�i%<=���^ ��g�8�r7s���%N��E@v�sl5\rp��\$�@����P��\r�\$=�%4��nX\\Xd��z٬~O��x�:��m\"�&��g5Qn�(ൕ5&rs� N\r9���.I�Y63g6�]Qsv�b/O �|��@�y��^ur\"UvI�{V-MVuOD�h`�5�t����\0��T�,	(��q�R�G�.l6[S0@�%���C}T7�85mY��)�8�C��r�;�ئ)�M+�4	� ��4��|�Ϊ1�ZJ`׉5X,�L\0�7T\rx��H��dR*����JЦ\r����52����-Cm1S�R��T`N�e@'Ʀ*�*`�>���\0|��I!�E,�ag�.��cup�Ý9�`B��aa���p�`�m�6��R~�\0���g-cmO��1�\reIN�QN�qo\r��nq����R6�n�Sn�t�w�æ\r�]a���-�a*���\\5Wpv^ OV`AF����3#82p�H'J'nM('M=j�9k�ZbBn�<�@�<� \0�fe�:\0�K(��N���v����-!�1�ލH(�Qg��µ���y�<�� �d�\\�c\\�s,u�˃q0�~�i~���e�Ѷ��*�~��Ƞ�~��M�m���}Wؘ\r�� �@�\"i�\$B���c�g��5b?�6!w��+xl1�`��`��	s�� ��������.�vCnhEd Q��id\"6���`�\"&f�x�(\"�2�Qz��\$�[0%�0lw u��>w�%ر��%�w�Z�\"-���u�%�����Y�g��>x\\�-��פ���-v�\\��x^'M	�P���Y�P����)�8�%�C���@�DF ��\r@�\0�\\�0N��.��S\$��YI��C�I�� �i�>xP͸͒�:ͷ�=��T,�'L��ٞ�q�Q2͌�\r�����d���Δ���@�ђ���9F���`�O��f�O�w�\\h�=�}S�jGGW����A��L��RJ\$JP+�7���L�v,ә(̵���ZP�g����&z+����j���˘7�ͷ��-�vA��w��h �^9�T�OD���Z��C���m����`O��R�yӒ��!�Gvzs��G�\$IhY�����58��xF������Y9����i�ݍ8��U��C��[���e���Zq�uA��1���?���و�9!����:�ړ����b0��{\r�Qh`Md7�{2� ۲8�H`%Ƹ��{-�l�C�Xk�H��Ӟ��|\0�}�X`ShխX�ց��\r���O�y��X��� �:w7�����n�鲌�#�/�:4�(M��;���c�D��z;�Z3��������]��砛�?�.�����\r�o���bO�^`Ϻ�|��������/��X׎�]�|�����^�!%Xٽ��8���\$�;��z�T�xK��-~� 8X)<!��y�x�9�����:�� ىF�������x�z+U຃�����A�E�;��'�%c����Yߪ����w�<{��9���V:��`��ʇ<����G�ء�Y��\0�Z��U�Zq\nmx�)_�}�YǏ�_�z�����y\r�Y��,ۚ3�L��٪�Y��ٸϻ>�M��	�M���	�)��P\0u8 S!Z{Y����9θ���fV3�o�Oϼ�E��`CЭ��࿿XU���}�lw��0�}�����͙�7�Y3ӬӔ��4��G��J��&�ä��ͭ(���-A��V=f|���@E/�%\0r}�ޮnn�\0��Ly�����<+���_��|��#�A��\"C[y��EW���rW��f(\0��Л�>�)�����_�U��,U�\\�#��e���*r�`�N�Y� �*�=a�\\��&�^g4�mü����e#��^�|ނ�QXN����I�>��\0rƉ���4��^Y�V#�)��k�>�׾���Ι�ԚF�W^��%�ݒ\$+�ՍP�kY*u�~��,��M��W͂hhG��K��\\C��7HmZ���S�Z_U��%�\r��b)��gg	q�������@@�����΅t�\rJ���۔��7s������U�K_1��t�j&S�Bi\0��� &\r���`�:�j�F�~=T̪��g�侑��!���^h�^�ו�������/[{�B����(�/�|���g���j/�d\\ޖSɗ�9���G`��u��1�M��?���3}�Q\$q�I�m~����G=��oVz�\0_p���!t�r{��^Z&��	��u�X��1@��G{���Ь�	NI������\$=0�Bu82�S\"�6��Qpj�ov\r<��ɶU�\0.�����E�M\n8�V�oQ\\�`?��L6���=\r�l�����������\"�����B2pu�&\0���5�\r�j��0V�A�����;v\0eH;��ʇTJ��6pH?/\\�H�@!pp�C��+5�\\+a�8;�\r(*��T�Ƣ;�O�|���^Ld�&/��NI�T��|#���G�`j%Ǘ��D����Z�ġ4�ni�i��4��]@t��#5c�ľ��	�Z��R�yR`@ँ\$I{z����胇��4|� ��׉�܀@=hCE��H��, ,Z����i���K��àP�|,g�z*�����E)AjknK\n��C\"J79�}4�f����*�4�65����׏���Q\\���c��M��\r�{��*�1j����lF��m�4��M�*�`�X�G�D�A-qqab���1�9R�H���b��g8�+�l/������ń� (�ʀL\" 8���0(Dc��#ihc���`��8���A1�\\�uK(�4!�������d��3�8����ш�Ʈ4�j�;�#��Ø���s8���5,ucnc�F�N��pPa8�G8�r�K��������ύ�k�i�˕4�A�	�8TҨ�26 ;*i��X����2%M�BJG� &�C*1T\n4	-#�.�%���'z�#�8�A+�@S.0�׀���II`U�Q��U�dd\$)��*]���T����C��9M*��	\$b+��ѽΑ�ydt�\0-��L��8\$��e\$���<Aɍ!�d�\$�p@]�d����&��M+2E��y߈((_|�Md�vU�9!�eD	�(��W=���#���_�'�bN�;��'��\0�O�<�L�iA�� Р�T�����\0�Q�J# }Ba(�/�u�GB���%-)��h��u����~\0�I�U��Pr�+1�������%51��ɒL`ܞE'(�/��Q�Ô��%T�)9�Or��T��],�?�<�a�	����/|�\$O�@Z��I�XN�|�%�,�SK:]ha��%��)k��P\0,���'�0J�:��	��&����V�0����jهJM�*�x���P)���j�K��R ��\\\r�u\r�(ÐW���F: k�����\0��NJ��P!�Q2 'H� *\0�g�T|����~g`D,�Ͼ\0#�	�;(\0�� ��L����f�5�'��`'���&t(��L�g�A���\0��'��ksi���&����d�m���P\"�Ng`O�&����� X@�	��%sh��g_�sb��f�5��M>s3�@T��77�+��nS��dӧ5'�6s\0\\���\0O:��NLS@ P�{;9�Ͷp�F��@�78_�l�9��\n���)�Rg�9�@a�:i�\0�vS�D�g��\0�S����\0�s�M\0B�\0�+O�q�`��>��4	 T9��7=�M�v�=q�'y;�'L�f�F��f�)�ϖwP��T�f�>\0�O�|��?0)O�~�|�����`#N���\0��>�'Ϫ}�ՠ���>�~��e	\0�?�*P��3��\\��@�͌��5\r'�C��P�� O�E\n�MB�#к�T;���=jPރ49����Ez#NƉ٢���FY���\\��\0CA�QJ�TV���������7 \n��v�0@��_��Q�L�RRc!��V�|�z��6��KKю��eS������4��\$�aI��|P��A+��.qKD-�S �EvbCO�>��H����<��\r#��LPܘ�s⥺P֭2�0� =�*��WL��2d�t� \0�!��<	�b�q�\\pa@�Rd o�fKM���p ���\0}����z�\0�����2�Ձ��3\"� �)@\\*g�r�M#!�<��O�XT\"�`\n];S�C���Π��5�B�cP�Ӳ��[���\$4p�&��\"��i�NP�Ӡ�'J�\rE�&8z��p�@>��BR�ݛi\\��uD*vz��Sć\$*��TZ�\nd�6�i��+���J�D�1I�:�=�P��������\"q@|�p��vjoT@SS�ڦ��*���'8\n#����֠+`ɋ�����SC!���:Q��j�|�gXђϰd磬%a�X�^O�uG�e��'��p\0{V��\0�gQWxX�Z��o>���B'�= 'L�)�v��\0�7�1L���k�T�B�R�t�G���,��� �R�MZRWL�U��	��K	�Wngl,T�P�\0�:��`*Y�tS�W\\�`\n��S_�_K��kh&[�5\\��\0��U��� ��\0^\r�rC\\�;�5E���?W�%�:��\n!PZ���BwWW��\0{�7�\$�+�[�1h�B���\0��63.kw,l0��f��:���5c�2X�F�*�j�2���Ðe�ee�L��&��}7��,2��߯�y]D\\��G��.g2�8\nj��][]�M	dc��}{�T锘�+xG�s,�\"���:vQ�\\n�H�7ڟ�yu~ �X=B�*�d�Q�Es�M���u?Z�FH��N�,�D_�R�\0Mh�/�~�y��|C�V���^�5R��2%�Z��V��s*lo{,[�vB��1\0Ƭ���>���/����Z�R�a\n���E� �A��*�a���2�\0a��@z�\\70�a�r���ǩʐvfX�°g8�Z^6g1�N�o��9%��S���t\\�OHH���\r��\n��W^&�A����/��4�{2��<�v�&Xi�_���Ҭ�f��Z��Vз�\\�t�&��0\0�gb�;�J�I�,ˠ\n)��.R�\n�T-yD�Kx¡_�/��x:��H��W5^UQ5���sUZ��IUwfF����R��S����}�Dnz۶F�����\"\0\\�1\nv��W�++g2р���SR�K�C�b�L�%&=7�\n	6��\0r )��\n>g�1���ބ8)Xo����\$U�;��P�V��z��J��}�H;r+ѝdx��\r-��%BA&\\;��'��om|za<n�p =�^=|��8�'@�`6����teD��)}q�_d9\n�	A��#}��_0��m��`�{��\r�rc>�篁~�d߰L�?b%�/�}��\r���>�;\0\\�`ցP\"-!�n!�����7��ϯ�71̼@���M���B��*�A�jn�\"`j���Jҳ���1�E�1m6��/�G�_��b ��s�_z���'~�]L:��\\���cF�a-8()�G̩�a�f,���Fp��E���?�X��)2���\")pi����4�1��������^���p�X5���P���x�܋�w�	��~@���5w ��Ǳ&�J����z�jl�d�	`P�������\"/d{8�����X�Q�d)8��	qx3����ņb{�,1���qM�����>D\\X��3g����g��Ťn�I���b�6xx���3�b�dnU^!�&�|k�	9=Yv���Lq��\"�:��D�+���w���y��#������t.�X|D�xn���<qI�|Hb��ĕ�r-���n���,Є�:a��^�.��ȞF���[_̭����/Oz���8�����\0��;���l�HW���m�O(����W����'@�\$Ȱ�ǒCt	>`tQB��������lm̉8J�O���m%�`�^Kp1��Q����2K�\0�=��y)2׬�4��P��7#ȳ���ċ-TQ�>���.%�\rܥ�Ҩ���k��6@�f@�y��^��;��������HK�>�h)�\"�R2E(̠��-�H�0�e.p��\$��-��7%�)vo�\$/���+.����ce�;�^���ʁd�Wy���v��@q�47Fy;�lY���\n�xJ������l�=��x�&-��]�*d}WA@.OM��;<ک���\09Hl�D+\0�\0�&�UR9���œ��&��&`c�΀C��D��la�}ܩ�=h(�D@ЄG����hP�W�f�@�oB'��@�&H\0���;�;E�N��-��zhoC�9��4mM}l� z&�4v�\r{Ez���4`?\rh{F��n�@ӥB'�қ�d�f�\0�E�B#xZa�v��9=7`����ٗ\0���\r���W����>j�\r�I����f�!�������+S��<e��6���i��f��T�Ԧ?�|��jH�Y��Z5S�9����6�KN�G�}+�x�B�En)Dh舋���n[1���g�ʙ��_�}�v���U��IX\0��:kSA@R+���V��?�b�����B��jB�7�A�X�p6j#Gy��X�\0�R��Xf6��C@r����P���ֹu�d���&��&�@�����z�a&���%�|h��Y�G�/����y(Ŭ��@�|,�d���5۟��0�l�eV�@޷=��}^[+ىW�lR��PN�,ٶ\nK�Q\0�\\0�0��C�|���i�,��0ͳ]|�Og���٩���q�'��{тUD`��eLIP��!W�����tI�_�C�bǄ�Ki�ix����Q;�j�z�)�8 2���b�����\0�FO/%��c��v�L!�S^\0�[�T�!ۚҍ�֚�=h�`�F\0D@�y2E\0�ڌA �՚I�DF��nf�M�t�f�i��Qb�J`�O\n��z�WV�&���(��(�P���YGH��嵮T ��ܡG�Xp�I�-\$懃@=�,��L\\�������o�/�T�����H��w!���XMz���H���:wK�-;<|�{��o\0u�X��d�?\\�f�F�{ˆ�Iyܲ�I/q�ݯ��3�(�S�@\n���̍���w�	)	�m�j����Tij3��X��~��!\$��@�G�(�8o��)�S!o��`{�~��It����<>�8r7\nmÖ|<��?E��7\0�9�W��Vk�8������%�Q�a�s}�����7���61I����M�]�/�0N#o��N�4�����a-�#x��n��N^b�mҶ�uE���P�kT\\�/��Ȍ��z����w���^Br_��-x�E3��N\0+)�����xS{�d�,���%쭲����B�%������ìi�_�pW��H,O��zLo�QQ7�q�<F�Pz�/i/3v�t�p�D�l��ps����ri��k��W p��:�p�����f��}\r8gA�����+:\0lȮt���S]�垩�ν\$/dt���P-�U�،�u�D���>��ܶ����WT�e������\n'�p:e.��?2���GrS?<��v��9�����{�2R������RӯX8%�!lZ���t,�M��Sf�Kw���8_�>jsl�\\��77�g�.�݁��:^e�P���pL�K#@�`�\\GL��5١%��B�@�'zQ�m�����m�\$��3���!�\r�V\n���	���\$�YT���02���w�% ��,�S�]���s��:u�dc\r��3�T�ၻ7e(ؿv��Or/��he.&0����ns#���Y>#)����c_Rш��q���x\n����ZR�jp5��r�n�Q���?��=�i�\0x�*�-����{�.�6�F?#1G|��\0.�x#*�nc>�`8k�%`S�S�A�\r���0��7�\n��m��ڜ[��F��;�F��\$\0T�k�_��_�nv�>8�����2����@�h��W��쩜,m�b*v�\"��0yv��p [�<��#�/�!+{��7�v0�O��_7귻�@��\r5~\\F̰��N�y�`���z�}	�o/��лU�ѻ���8�{�#Zϣ�u���턖���xk��7'��{de��y\\G������ļ 1�  d\$@�9\$M�w6���W_d���@��\\=�)wgsP��=�����=s�^����t��z�g�h�}C����njz'�{Y�wi�_�}��4��\r�3٫�\"g�9�؞��!r��:y����� �%������p�C��e�׿�3�\nwd�����`�pXR`�]L�����e�jDjUtמ��w�>��_��+�X=��^B���\r�K�b�ߞSd�4�6I��P��,^9����áu,}0���x����\\�D�!����:k뗅��>`1�`:��P���_\\To׉\$F�&�T��i0Ⱥ�����_n�J��/[�	~��{���۷p����C>��n��\0��f�@��y����[�?�D���G\0���4Z\0ۍ=�ށ,�!�S���I\"�Ѳ���F]���_%��k߹��_\r����;�1�v?�t�T��\"^��8m��e�^S7!��������@��F	�x�f�Б^#��'��oq�������Ě���w��Տ�E��.��O������Y�����(o�����qN؇���yĤ�p[nw3:�봌�;L�ہ����\0�;*�P6)��*Ȏ������U��д�=�\$��\r��V%\nRRA}���\"�f��P��=�1�4�=&:>�\$�^RMp���\$IL\0�1��𥀔���}���a	H�z��	��	���@�1�V�=&�L	A	@̔�T���<\r�^�j�x.�%�p.�\0Q����9\n�(�.	�@���`(�J\0ʦ�>�����\n�!@2D�֒�\$iE\0f��@�v\n�*`��d�W�s�\\�|�>�H��*��@���Q�j���P�;�|<|!I����0#@�=)�k���X0W��1�ݐZ@�U,D@� (P_@�t��p)A�/�c���0-Am��k��	P2��BQ�5�T1A�%{���=\$�PI=K(�T�V��*@sAҢ�����7@�Y���̕\$\0���`.�9����A\$��Rp{����PV<��(O�#\$ po��*dPNA<`� AG���@�TD\$�I�QBLN�C�=\0������[���<'�A�	��i%���У\$BW�6%S	�\nЙ��L&�@}�p�A	\\����`��#t�ǭ�	�,p&'z[��C�	`5B���\$`�At�BS07A��L��x	�\$�	�� ��bBA	�/�B�U,,0\0ޖt�2@�l\r�RB�i/�����L.r���.�_�X(��\rC\n�/�B�\n��A�!02C5<,ЇB��.ж<x���-�t�]PW�o����N	��ֲ��@��J���F@�.�<��\nC��`����れl����\\\"�� !����D�;`3�^�\n@��'�	C`����(��(���&��\$��\rL:����ð���AjC��<�<�vQ��0��\0��C��XH\0002�^;]	~���ܰ�c�D1��(5(���49�FQ�� �M�E1�@�;�?D\$k��AD�#DsDG�D��I�fD�J�#ıTKQ .���C��H��ĭ<Gq\0�=�!�jC��a�q �5	��L�M�=���ˍ@�O�;��\r@���E\"�iq�gO����49�T��+��D5�?0�������2��AMD�F�7đ�E�7�\\�F�R�dRQ6D��N1�Z�V�r�A+vE\r\\M�D�LV1Y�kO�c�k�YqXE|X@�C�2c�iD�lLQ^śY�'�QoEY,\\1fE�\n��h��]Qd�]W�YE��F)#�h�p��<|*d��2x��7��;��G�^��\"�\$h%Jv\0�X��/�+��w3G��b�3��q�Fq��5D9�Y��\0�.@���69\nt+S�E�*���4\0D����qS����x�8���\r|g@�!_��1�����\$4��w�~���1��n�r�\0*\0�#E���0?�	�'\0d� 	��(Hx�F��\$��\0�H�`(�������x�GD�tH�!�\nɁ ����FJJ|�6�>?)PBQF�q�\$`�\0>/j�T�\r��Z\\s�:	(+ �En��!Fq�^��֬s1n2��dD1��x\0a/�~F�|�xH�>4Z�\\�6�П�_��5z8��y�P?������4 7�\0��\$y�0�Z�>\0��|��Gΐ�s,������^DS|s )��X�5��q1�(LG#�[���'HL�i[��H&H	܁�1 S[&y��K #G�f�K!�[i\0#5�i�+�\$N(��-Jf����&9.E��\$�T���B3!p��\$�0����r���:��e�,���[�0���1Ϻ|�E!���\$,��,�K�@; ��0�l\\�~\0��L1Q��و\0%x@3���� 2G`L[�ܯB��b����2d�L\0��I��������n+r��;�\n:��7H�\ri���\$lO��I4Xd�\r)D1�r�5)��Q�W�J��#��H0Հ<����%hrE�����QH�\$�s#	�ؙi�W�f�gHa8!q̾��D��	^H�T�ق9%̏�\0����H�!Ѝ*JGi�\"�ߌ,M��\$�����M Y\"�II��X;ɟ)(�@�s���P��\0�����/a'PO�����\0!`2���Ʊ��,�O��r��ˀ�-Ĝ�::L���D~-�C�_�F�R]HF 	�x9T��r����Hb*p�[(��2��B\r�T��;-(�GҎ,:�|���\0��f���jD<f��p��@)(Ó�i2�(��O���[�����L������2�\0����'�H�1P��g ĕ���P\nԕ\0�ʵ%k]�\0�(�b �H�(��aHH# I@)�ҙ\0��)��+C�J�\rl�oN\0�̩��������	�n��g,0n��+�H\"�I�7\$���It��\r <�a0H@���+���)�d�!a��T�r��D���rՀ�,p\rD��`MGX���N=5 ZƯ�><�P/�^g`���*+u�i%m%Ӧ�e��'L ��ԚҞ\nK.�Q���),��˫.�!�]K�.ԧ�gK�\"4�\"݈�(���\$y-ø )��W�r��{/�M��K�/����c/£R�,E �Q�A\0����\0�HQ�BL\"Ҥ��2�/\$�%/��3�/0��2<L,��s\r@	-���y�`;��2�\$N|��Y�.�Æo�0Ĝ��c%���k˹+��@�f�ܲ��^�s/��O�̄Bi%M�\0�2�\"W���+A�!\$��)��%��L��2�9/�0�ˠ��t,�33?'�̪y̸C�f�F���؀ħ�\0��T ���6�pȋ3�2������'������Q#����H\n��UL���/���۷\\X	�j���C��M\$��w�T\0�)4�U\nL�k�O{��\"D�!��\n��\0ۂ��h>!?�G5[%��t�_(�kQL�>�ȫI������ə\$,f��i���Ǹ�dڲǹ#��sl���\n�\0I�JK��\r�m%09ͳ��*�!�E9ĭr���*i���̎2S�w\$|�\0�J��a���j�[+���)ρd�K�T�#F8(�a�d���/I.,�MtH�1�P!H)����R�N<c��oJ�Ԩ���8�j~ɡ,{��Z�@\n��?�8q���i9Ď��J�\$�I��8S�\0)GC9�b�\0��鲦�Y%iv�\r��)0���0&	T�����L�G�|��\0�-q2���+d�o���8��KL-D��D�%<��t^s�\nk;ĭ&o\"^8�:̊��P\"�	�;s[3�N�5��,9�5��,��c��r_�̀�Y���c-<�1�\0���Fd���e-,���⨨Z����-�����eH-�̇�=����_-���̉_-r#�<M�-B���͉>@\r��Τd؁���:�5�̚�.�@9ϭ�\r��E,`(81�����^ˊ	P2\"��+q-N�&�D��&�!�9\n�+4���HT\r|����4I�#�Y�Z�;�w.Ě��M��b������K���s��;@�(.�J���a\n\0�ILsb�\$�Ɇ�N\n���so	_3��8�5-t���/-pC���;A�����)�����?�S���+�\$1�4�賑�D�\r]\0002\\\0�����L�)�\$�2\\��2M�2����(��@��G2��ăw>d�M1P�8ms!W(�kQQEH�T�\0�\ny�`������p�a'��G�B��'�s���HHT|u-�L�C�B��K�\$DvŸ��3�˰A=�W�.l;!,��B��P�/����b��j�L/?�����������5T[Qp�d�[��D��MQt?��^��E��QQ[D�?T_QL)-�:Q�EU�!�CF\\��nJ�L��̕��ѐ�ilQ��l����\r�G\$�r��E�Ty��F�T{Q��4gQCG(9xQ�F=���G\\��}Q�H�`7�mĬ���-!���\$���� 8%T�R,R\$lԍ�mC���Xd���F%\$��T�̂��ɋ �g2V�T�!d��4�(kT=��+�4��Z�� %��~��ɔ��T�����+�R�;�80\r�����L^���QZXQ�6��8}��I�9\"dewK8;t��F]-�JO���Ja�Z\$`~��V��\n���-�0��AK�a�p�%X�\0�&��)��8�Z�;��0�A?I���#\0�G�M3� �F���H��\r���M�'HQH��\rx�V�^�3�M�M̃D�o0\$����0�yR�;��0I?!(DRͰ�(	��G�O���3\0\r+��`RL�`��QI�����T�r�Q\0#�k@ �O(�a�C�` mj���������A��\0>\0F%��)]H��\rƶ���] �B�\r�3��f4�&\rR�Tj�\"R�]/d��?�\n`Ïğx\n@ �r?C@(�\n��T4\n@�&\0�@@	�r��\nx���\nd?\n�\0�P�zUO�\0\\� ��(\n��'�`�DHd��b�aRD)a�p1Z�F��'\0�\rO������[�KR�PE@P�TU@�TT}Ag!���@ C���_�v��s����ji�F�?X��\"��?*�0�&�g!�%Pm>����O�?�.��=,,��T�e	�b�\0^�(9�qO�}uJ��ʪKԟSJJP/Ԩ	�J�ET�TeK��T�7�l\0Y�'�MT�P��U)\n�Z�MԖB�\0��x	�Wл�AWkÕeP�]��U0�-*����W��6ը3\\;��F=VY��N��4=�h��V�Z��%V�[`6U�,A!M3�)0�����^`9F(����|�W�x�YU�W�i�<��Wڜ5��NH�^����a���Xa�H\0�?ebc�Z����XC����X���3����dU�V+\rdT-gX�y��V\\�dIVD�i�z�YXSH�V�dA��W�hg���b�ڀ�7�9�+\0��EZx����}jc@V�X�iխ\0[X�P��Z�j���Z��5��Z�:3<Vem���X����[�O�4V�h<U�L[�h�M�\0O���&�Hmu�,j/cr�+(\ru���\\\raf6V�Z����V��@7\0[X�,4��\"����?��U�SZՃL��V`\roU�H�����\nM<r`��G��@/�\r]�K �WlP�%C75�E&-�\r�<�`+s[�j���܏�W����\0�^�k���r��Z]%^�<W�^�{��.�5f�1W���:�W�Z����&9^�wq:W�^R�u�ׁ]�75�Wy_�P�Y׏`Z��5`0[U��o_�Vi���l����q`=V�~���@�?+_�*6X2��Bߌ�C=G��@,��V�5^X�X`Ņ�E\rBx!�2�Y����/^=y6�_�64�`my6Dh�gi��z)5�C\n��J���[b�v\"ؤ���֝_��v.ح\\�[C�\n�N!%5��0e}AM	�(�g�b2d� &9a(\"QF��c��6\$X�0e����\n�j��V��VAI� s�CVE`�>�|k��G���5�tr�d��VL�Z��vNY\"E�r��e��3��\r� #��XU�T�Z]�e\$�^\rs�W�}y?�a`❖V�a��̨�Jr����X�^]��ׅf%��e�wcxCRSX+f�wVi5bU�@2Xx��6;Y�]�hvpي,���T��f��ɝ��]�vl���Gj~�	g�\r�^��^]�!jX�f}~�qY�2\$\0�� ��uٱg͛K���m}�|Y�hyv\0�(}�\0007I��+�o�,�\0���0fU��6�������,	U�6��[h�=Aj�@����=c�q�s��c��쮅5h���\n5d��`�X�5�B���s5��`��kaTVQx�i�P֗���a�Bڣh��օ\$6m|��Y&���}��j�?J~ֿ73Q��Zh�����X�5�eh\r��>�7-ymWа�z�Ѵ�]0i�YOge��W\0I�DU�4EZ���c(�uk\0�VųwD9W��.�k���>\r�.\0�����֢���}`<dG�Z�l�u���,m]��Z	Wm�����6E�Ad�2�����.����Zmŵ��,Am����[�#���	1d=�V�sn�t6=��!���3[�n��SV�e�����Ume���N�^�ֈ�ku�v�Z�n��6�ۍo��`�םó���a-����r��L[couZ��[o��v����\r�@�[x>q6�	��\n�( МUgw�+pe���Yymݿ�	��n���\\U]���\\\\�u���t�%�c5�}fd�Uz9-p�\r��?g}�J�֏B\r�N`܉r5��%��b����V�om���3v�)��սd%��r͵w�*axZ���qV7)U�r����\\�e�Ƣƴ^vj/a>�Gsu_W8\\�[h>�\\�q��f�[mb=�w�'�X�[!W�1FV����[9Wm]�5r���`��r��w�mc��/��u=ϕ�s��:]Gr�e4��YVi�BA���o҇�7Z2A��sK�>����>���)\n�i�#&{Q�`�@�%�)�r���N� ��>��c�V�\"q\n�]vڅn��\ne�@\$�ޛ��hB��\0集�v%� ��>�鸧�@	��]�=3\0*�R-�J�T?�\n@#��>�	�(]���r]�vE�e��w�0�h�xx`)��Qh�)�G��+�1vM�J����	�ƀ�;\\����?�0����@\"ݴ���+�*>��\0�g�(>-E@!��v��wv��(�u@^mx���'vm��]�v�	��^�?\"qWm^�v`,7�ސX�&rJp��^�z���ޥx�������	�!���3{W�ޝv�ۗeݾ�-�W�\0t-��\$��������v�\n ���DI�^�wPi�����W��v��\0(&�\n@,��x]�׽^�|����wؕ7�\0W��׃^W}5�w�&�(�����|�`&]�y���]���ׯ�?��A�xU�C�޽{u�w�^/}E�7��x��w���\nE���{yE�W��yu�\0�?]�']�{��7e��|�j��_�����?}\r�w�S���֞�W��n}�7���y�\0��_������&-����Q}�k7c^1y��I��y�W�`{������t�mTSQX��o��F*�_)Q�g�^�p\\��\0���G8���ڀ&�?���m_���ꀘ�	��`~?�\n�m'�|��ױ��=�bU�H7�1��.'��U`��\r�!��{���'��x&�1�=E�Ta���i�̫�\r�!\0�|]�8�FTU����`�|�o��ޕ�����y\0�T�?����G]v�ס]���\\�&��>뀥v��8F�!�]DW�GcP�	i��O��U�����D^�|]Cj�^(���ݙ}z����p^��7�\nx\$a�?��c��D�i\$�?�����&�\"މ�xm�����l�E� �W�a�}~a��5����Р���?��HC�C�?@�@(a���Xja����ޯv� 8}�{*�8�\n.\r��)��8m��������c�b�f��aՈ���ʀ�}�\$\n��5���r\0\0�2jɬ&z��#��=C���ܭ@����?�	��*���;_�f�*+Z�n\0\$�qQP5\"ݐ@0\\���x@Mر��.8�b�|����{�av\n��b�%/����a�~�8i����>�/�>8�bՇn#Wܦ��P���⧅��8��=|zkjT��*m���� ��e���0�����u�J��x&]�E���)K��\\�_A~�w�c?��*����8ӏ�(�w�Tq������6�3�~6��_`��]��xq`���-�I��&6���و���c�zu帕cw�&-���݆���ݤ������?V3���*�@\n��bɊ�6�v\0���<���S�x�\0V��\n��c���	)��ox6>�j�\r�����c�h\nx���?�z9\0��.��`#��=�	bE}�>�+�@rx���w0��e��0��a��& ׌���C��]ي�x��_|,|\n�bL�EU>&o�2��b�֝N)X	\0�4|�&�kW���bzX�����\nf�vdIw^&�����ĝ����na\0!����D��&���y2�wX	���ҠE�D����u�7�у�+�\n�.x����-C����P0�]��:��s�-|\"��!`tX@'�n?.R`&��?�g�r�ߏ}C��cK��\n� ��^SM뀯w�\\�Ï��\"k�`�y�8 /��? �7����������I�a��\n�\"��`	�*cB��X�c�^��Y@�&{wx	��Ǿ?���>_�·��e�v�[����\\��dƚ>��S��4�\$���]��e���^!�e�X���&�S�_@(d��F]��堭:�*��}�z�i���kX�_�za2��\0��rC�f#v��ᙘ��9��1we�1��o�n���5��d4_f(�^eY�F�c���A��0������X5`Ҟ.��U�n`���j�<�D���%N�⧥�6�#]��Y \$Gb�m���b��b}�f���C��ᅓ0�UT��­x#`��kz�ԧ���m)��;�^m*�_�N� ᳇(	�f-��n#�c���o��\n����)��p��Ǜ�g	�+U���\0�E�p��Nq�&��5�  ��T\ni� ��{���a�v.rJߓ,?FuX��X��zp��(�Rɖ?(�y\"��S�y�gH�\r��?�T����#�S���gs��	��\0i��)�*\0q��o��\0��Vz�\"�A��jX�e�>��(b?b����w0	\0/��w��	�(b�r\$�՜�q��)��rc��\r��~���A�~��a�y5�W�(b�\n�ɟf׌2hsϦ�zh`�L�y�禹���0'/�G`\"'�Z�*=]�}���9�j�Y<��5E@+��L�}����?\0�5\0��&Hy�fo���ؠ�6jI�\0���{X�g%��~9���xg�\$h�Sz�!����P呀.X^z���p��s��'�S�����~Nhz3G��VD &'ڢ�~t[�㊮������z�c�?��-��{���b�?�&��޸�V����&��ɞH��4����OX���S���\$�͌\\�\n �ɕ���	�~� �I��PY�h���q����Vix��EP�1���\"N7�h�vVh��dEx�j��^�֗�;ϥ�C@)dZ����-���/����`�[�a�u��h���z��|^�	�饃P�y++��eF��f'�F.��&z�R�U;+Z�#PY�ݺ�����5���W��|.U�hUw�1�*�Q��*����p�a��C�����H)�'p�j�@>eɚ�#���tw�g#��jW��Մ��	��>����6@\"@<���ʝ���f`d�9��[!\0��C�\r@VO.(b\rl�/! ;@ڃ�-4��I�L�B�TT�:��o\"��aj��\r}&��bT�!R���ٚ��N�R��L���G`��3�j�dYj�^hzr��[J\0IS���>�z�\0۫�]\0�j�?&��ψ `.�l��i����{C:�R��%\0003��j� �W泺ʜ����k3@]��3�*ѥ<����{Vc���M�A[�\0�y@<��?�yC��㚠\n`++O��|��b�X��c^	~Ny)����	���#��F�&����zX�C�&��	k��^QYJcI�^N��뵍N4��`������*}��k�}�:�k�z%�w�k�@H	����Ri�]���w�+,�C@Ø���>�HJ0�>�\0��PUT�� �v���\0?�\nj�FI��.��Hж���{4�ɨ]�\"��U#����)!U)���!d��\0�\"���L���T�J�����@\$��K,Ӵ�%�Q5����r���M��Ӻ����P#��%��R	01����]+�8�4�F���@�`d�ؙ��<S�)C��9����1�l�D�r��#\n+ 4�kK8����K!�{\"��*��>\0번5{%�\$��kA��Ƽ��A�9����T�Ǿ�Tr\0�O�\0as����m���hȕ���/�f��O`�m�	��	�tCz><tD&�\r�ش�M@���R�ur(m )�R.��bQ���j�Z��a)����L�l/۫�%W��F;�8���m�/�}�vGaB|~�m�+'�vm�-�s'��6���P��1��Y�����\0��IB�˱����l�.8o[zR�1��1���r\$�R@\$S���̱��\0�N!F�aX�f�RV�h6�s�\\�P;��[G(Jc1j�>�!�Ѷ\r���nwV���͓���:�\r�L�o��m�黤\n˸��:�=���b�}V��[�	_�`J{�#!�?3'����ߡ����{���:`hrZ77v�r8�紽�AT���x�nJth�����\nh�aV{Q����W�4́�F,D���C��:�i[���h���`��Æ�ݼ�ee���3σ�L��;IH�El ��d���P��/M\$X�����3|�����!Us6���a��C��#	6��ҡ�s�{\"�Z�V���c��!��k�r_U�0V�m��%��<e��z��4�� ������d��%C?F�[+��<�!,﻽ur�[baxg�����%��!��#o�ZX8!F�\$M���:��2ml��OX��;O�����#`mwSS>�lrNk0���@3N��3I�>����n�FD�`_Z&|C���`�Img��V�6e���u>վ���k�j�j��=�A�G�Os��dp��E��,��'�,�t[Q����@�	_�x%[I���C�c3�\n3��6t�!,� ����	mDXr��5���3J}�P\"[���4�CNL+q	�<�\$ZX;����@��I܁��.��\0<��Z�V�r#\r�[	4\n���`F�\0ɬ��q�l\\�� �k��#|Z�%K0K.\nIZ�̠@܄��L��-�Pd�^��1S�������D��H.�iZì}�Z��\r�ͦ�m���ھ��\r�:��k�,s��\0��G�����W\0001\0ȼ���u�ݬ_\\xq����mZ��G�6�\r���?'b2r�?5���G@���o\"\\�r\"o yq�IG!���_ǯ#�k\0�['St�Eγ���OK7%2c�?�n��<�4'\\urf�A\\'H�\n�����WL=/N���u�NmUwv��N�E������y[G�P&��2�^���G?M�*f��M8�)�s����c���Y�uvk[E�:���\0ǿ���H9�x�d����`0��,hCB����)���{�J|��2[��Җ,K�s	�{A1���u�ar��>��+�Vaz#�͈W����ʭ�74`�)��5+�00\$,�sE��8;\"�ta	�W�[�����͛�p��\nF������d���[`G0&���� ��s�2\\ſ��\\�s	\\���͒t/5��va�����N�\"V	�g`�+bMpG�<1����(<�����D��sʟ=��sϴ),vN����\0�@�\\���=�ٹ��xa�v��<��O�@���u:��o�ϗA ��f��6��s[>}J��\\5���֌�\r�9�&G͜�Ҋ���=G�˘W��5�y�;\\Ū˧H<�Ӿ7\0SGs-͟H�&��H�!��y�j�{����\"B;�lk5a8T������W��Z�L�.��5��t�`�L�W���M\"�t��M���������IGM����f�7t��Ţ�u�9c�'\nW�*�6���&گa��\0�/!˓��\\����>������������� YC�ᆚ?O.�l�@O=*���@��W�����j@`���Ċ|��\r�p9��b�N4�W�9�@��M�P������Q|@�Q�O>]R�K��+��W�g[�����\\|�s�b�H�t�\\�K�r���;�y�t`/^]N]t�&�����g�W\$��f�_qOP�9�W\\`�]C��A?F�C�A�pj v%B�b���Ӿ���# q�4�Y5�P�4���Cк1T��oq�tAG�\\�&/k2}�<�L�A6�5\"Wg,�#�g ���Z]�1�¤�I�ń��GS\nHk\\��]�g=��(=C�/ւ�R�]9ک��m24������r�\\�ւ�����������?k�w������t�gP�u���2u�H�݉	�͓=��?�X��2��gkQ�����l5�֙ew8l_ܧn���Mr�v�w9���4�O�ws>�a%D�]�IE�e}lt�({�]�__Oc��vH�C�9)�m�u�M}=�\0�@07B	_�S�\\�2Bݽ���U� ���e]nB��M-v��\r�v\0��pMɕ̤��u!@w��H&���Ӳ�t�֖��n��u�\"_�@��)N���ڿoN�W��s\n��Q��N�lw\"�|��������AjF/-���w��4q}��e�ox���\"9=���z3_P��K��:�fN��h��Qs��_X�Sj���S����5�%��v���A������FB�;R��pGG�\00063���@61������{%�+LS����?+����f��Iu7���t��at�\r7����0�i����Wi��?�3��@�n��R�@��m��f������lg!��A�F&v�-������^\$�Y���aj��(��_���o���?��ە���rK�dC���3w�I̤���5�\0q۰�����;wj����[�����a��596��#�L�kvo]��d~a�\\���������'V�u�4�@��`��{�G-�_���j�eI:�����Q���ze{y�,i:�v���z�}`��I�%����7'���]]����y�_gI;��R	v��ϡ�ބ��x\rc@sk����&+�O���n%赵r�r�#t�'��϶4��WV���U��=�066�]觥}kS9w���zb�9>�Y\n�� �zb��M�Uze�Y2���\\���;�?���x4M��Ӆz��?>.�鏨>�q\"U`�Ӊܘ7;2��*t���aa n�o�%?�Q�����Vz�ܧ�K�Ϋ러>��5�A�q�'*������>�>F�ơF�Kс\"��m�/?<��k�)�ޞt�Z|�~ɬf~��M�%�������=\r��t3<g\\�yl|����l�Q`X�Y\\�[�m�'�x���\$©��\\sCㄺ8�D�t\\���\$��ͯ��p�w��g�^�%���a�W����/�w�>b���7�;�`[�^�J	��}s{�����]�o�>G\\��=��������{�\$��v��'A�\\���g��������{�O`�[��Ks�W���k�5�����*����ϯ�{��.�\0je���\0000����\\mzp	gA������Eٟ�����@�V�N����S�Ͽ��O�����,h�D����*S�z���=���w��+v���+|A�����P6*��vgBfŰ����J��WQ�g²��{�(O��bu1��J�\$���!�����^�!��g�CT��\\� Ԁʱ6�x{�Q<��:~G?��{7��\r�KuM�	X\\��])w���}+)�}SI��[�;����J稒?H��Կ�_I���G����B�vGtM��3|��C\0�p�gOR��-�H �@�q;^����O�IPʉ��\r��f�w3���ﶟ{!���C����׵ℏ�茜Huڐ�T�8{V�v��OHb1G�k��\n��C�8K�f��'ߪp��+׾R�����>Xt��L��2mU!���?K�6O�2~1���ۣ�))g2a�%�O\\�wZeȿZ��ei_�u?����ׂ\n��W6}�YYڲ���P�_~=��7\"�+���F���g\\��'��b�&h�Z�����IϜ���֯����\n�D�_~�ey�;\0B��E���OU0\$Sz��&�*oU�FH`",
        );
    } elseif ($_GET["file"] == "logo.png") {
        header("Content-Type: image/png");
        echo "�PNG\r\n\n\0\0\0\rIHDR\0\0\09\0\0\09\0\0\0~6��\0\0\0000PLTE\0\0\0���+NvYt�s���������������su�IJ����/.�������C��\0\0\0tRNS\0@��f\0\0\0	pHYs\0\0\0\0\0��\0\0�IDAT8�Ք�N�@��E��l϶��p6�G.\$=���>��	w5r}�z7�>��P�#\$��K�j�7��ݶ����?4m�����t&�~�3!0�0��^��Af0�\"��,��*��4���o�E���X(*Y��	6	�PcOW���܊m��r�0�~/��L�\rXj#�m���j�C�]G�m�\0�}���ߑu�A9�X�\n��8�V�Y�+�D#�iq�nKQ8J�1Q6��Y0�`��P�bQ�\\h�~>�:pSɀ������GE�Q=�I�{�*�3�2�7�\ne�L�B�~�/R(\$�)�� ��HQn�i�6J�	<��-.�w�ɪj�Vm���m�?S�H��v����Ʃ��\0��^�q��)���]��U�92�,;�Ǎ�'p���!X˃����L�D.�tæ��/w����R��	w�d��r2�Ƥ�4[=�E5�S+�c\0\0\0\0IEND�B`�";
    }
    exit();
}
if ($_GET["script"] == "version") {
    $n = get_temp_dir() . "/adminer.version";
    @unlink($n);
    $q = file_open_lock($n);
    if ($q) {
        file_write_unlock(
            $q,
            serialize([
                "signature" => $_POST["signature"],
                "version" => $_POST["version"],
            ]),
        );
    }
    exit();
}
if (!$_SERVER["REQUEST_URI"]) {
    $_SERVER["REQUEST_URI"] = $_SERVER["ORIG_PATH_INFO"];
}
if (!strpos($_SERVER["REQUEST_URI"], "?") && $_SERVER["QUERY_STRING"] != "") {
    $_SERVER["REQUEST_URI"] .= "?$_SERVER[QUERY_STRING]";
}
if ($_SERVER["HTTP_X_FORWARDED_PREFIX"]) {
    $_SERVER["REQUEST_URI"] =
        $_SERVER["HTTP_X_FORWARDED_PREFIX"] . $_SERVER["REQUEST_URI"];
}
define(
    "Adminer\HTTPS",
    ($_SERVER["HTTPS"] && strcasecmp($_SERVER["HTTPS"], "off")) ||
        ini_bool("session.cookie_secure"),
);
@ini_set("session.use_trans_sid", "0");
if (!defined("SID")) {
    session_cache_limiter("");
    session_name("adminer_sid");
    session_set_cookie_params(
        0,
        preg_replace("~\?.*~", "", $_SERVER["REQUEST_URI"]),
        "",
        HTTPS,
        true,
    );
    session_start();
}
remove_slashes([&$_GET, &$_POST, &$_COOKIE], $Kc);
if (function_exists("get_magic_quotes_runtime") && get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(false);
}
@set_time_limit(0);
@ini_set("precision", "15");
function lang($v, $Ve = null)
{
    $qa = func_get_args();
    $qa[0] = $v;
    return call_user_func_array("Adminer\lang_format", $qa);
}
function lang_format($Qh, $Ve = null)
{
    if (is_array($Qh)) {
        $Pf = $Ve == 1 ? 0 : 1;
        $Qh = $Qh[$Pf];
    }
    $Qh = str_replace("'", "’", $Qh);
    $qa = func_get_args();
    array_shift($qa);
    $Sc = str_replace("%d", "%s", $Qh);
    if ($Sc != $Qh) {
        $qa[0] = format_number($Ve);
    }
    return vsprintf($Sc, $qa);
}
define("Adminer\LANG", "en");
abstract class SqlDb
{
    static $Id;
    var $extension;
    var $flavor = "";
    var $server_info;
    var $affected_rows = 0;
    var $info = "";
    var $errno = 0;
    var $error = "";
    protected $multi;
    abstract function attach($P, $V, $G);
    abstract function quote($gh);
    abstract function select_db($Bb);
    abstract function query($I, $Yh = false);
    function multi_query($I)
    {
        return $this->multi = $this->query($I);
    }
    function store_result()
    {
        return $this->multi;
    }
    function next_result()
    {
        return false;
    }
}
if (extension_loaded("pdo")) {
    abstract class PdoDb extends SqlDb
    {
        protected $pdo;
        function dsn($Yb, $V, $G, array $kf = [])
        {
            $kf[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_SILENT;
            $kf[\PDO::ATTR_STATEMENT_CLASS] = ["Adminer\PdoResult"];
            try {
                $this->pdo = new \PDO($Yb, $V, $G, $kf);
            } catch (\Exception $uc) {
                return $uc->getMessage();
            }
            $this->server_info = @$this->pdo->getAttribute(
                \PDO::ATTR_SERVER_VERSION,
            );
            return "";
        }
        function quote($gh)
        {
            return $this->pdo->quote($gh);
        }
        function query($I, $Yh = false)
        {
            $J = $this->pdo->query($I);
            $this->error = "";
            if (!$J) {
                [, $this->errno, $this->error] = $this->pdo->errorInfo();
                if (!$this->error) {
                    $this->error = "Unknown error.";
                }
                return false;
            }
            $this->store_result($J);
            return $J;
        }
        function store_result($J = null)
        {
            if (!$J) {
                $J = $this->multi;
                if (!$J) {
                    return false;
                }
            }
            if ($J->columnCount()) {
                $J->num_rows = $J->rowCount();
                return $J;
            }
            $this->affected_rows = $J->rowCount();
            return true;
        }
        function next_result()
        {
            $J = $this->multi;
            if (!is_object($J)) {
                return false;
            }
            $J->_offset = 0;
            return @$J->nextRowset();
        }
    }
    class PdoResult extends \PDOStatement
    {
        var $_offset = 0,
            $num_rows;
        function fetch_assoc()
        {
            return $this->fetch_array(\PDO::FETCH_ASSOC);
        }
        function fetch_row()
        {
            return $this->fetch_array(\PDO::FETCH_NUM);
        }
        private function fetch_array($Ge)
        {
            $K = $this->fetch($Ge);
            return $K ? array_map([$this, "unresource"], $K) : $K;
        }
        private function unresource($X)
        {
            return is_resource($X) ? stream_get_contents($X) : $X;
        }
        function fetch_field()
        {
            $L = (object) $this->getColumnMeta($this->_offset++);
            $U = $L->pdo_type;
            $L->type = $U == \PDO::PARAM_INT ? 0 : 15;
            $L->charsetnr =
                $U == \PDO::PARAM_LOB ||
                (isset($L->flags) && in_array("blob", (array) $L->flags))
                    ? 63
                    : 0;
            return $L;
        }
        function seek($Xe)
        {
            for ($t = 0; $t < $Xe; $t++) {
                $this->fetch();
            }
        }
    }
}
function add_driver($u, $D)
{
    SqlDriver::$Ub[$u] = $D;
}
function get_driver($u)
{
    return SqlDriver::$Ub[$u];
}
abstract class SqlDriver
{
    static $Id;
    static $Ub = [];
    static $Bc = [];
    static $Sd;
    protected $conn;
    protected $types = [];
    var $insertFunctions = [];
    var $editFunctions = [];
    var $unsigned = [];
    var $operators = [];
    var $functions = [];
    var $grouping = [];
    var $onActions = "RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";
    var $inout = "IN|OUT|INOUT";
    var $enumLength = "'(?:''|[^'\\\\]|\\\\.)*'";
    var $generated = [];
    static function connect($P, $V, $G)
    {
        $e = new Db();
        return $e->attach($P, $V, $G) ?: $e;
    }
    function __construct(Db $e)
    {
        $this->conn = $e;
    }
    function types()
    {
        return call_user_func_array("array_merge", array_values($this->types));
    }
    function structuredTypes()
    {
        return array_map("array_keys", $this->types);
    }
    function enumLength(array $l) {}
    function unconvertFunction(array $l) {}
    function select(
        $R,
        array $O,
        array $Z,
        array $s,
        array $mf = [],
        $z = 1,
        $E = 0,
        $Xf = false
    ) {
        $Nd = count($s) < count($O);
        $I = adminer()->selectQueryBuild($O, $Z, $s, $mf, $z, $E);
        if (!$I) {
            $I =
                "SELECT" .
                limit(
                    ($_GET["page"] != "last" && $z && $s && $Nd && JUSH == "sql"
                        ? "SQL_CALC_FOUND_ROWS "
                        : "") .
                        implode(", ", $O) .
                        "\nFROM " .
                        table($R),
                    ($Z ? "\nWHERE " . implode(" AND ", $Z) : "") .
                        ($s && $Nd ? "\nGROUP BY " . implode(", ", $s) : "") .
                        ($mf ? "\nORDER BY " . implode(", ", $mf) : ""),
                    $z,
                    $E ? $z * $E : 0,
                    "\n",
                );
        }
        $ch = microtime(true);
        $K = $this->conn->query($I);
        if ($Xf) {
            echo adminer()->selectQuery($I, $ch, !$K);
        }
        return $K;
    }
    function delete($R, $fg, $z = 0)
    {
        $I = "FROM " . table($R);
        return queries("DELETE" . ($z ? limit1($R, $I, $fg) : " $I$fg"));
    }
    function update($R, array $Q, $fg, $z = 0, $Kg = "\n")
    {
        $oi = [];
        foreach ($Q as $y => $X) {
            $oi[] = "$y = $X";
        }
        $I = table($R) . " SET$Kg" . implode(",$Kg", $oi);
        return queries("UPDATE" . ($z ? limit1($R, $I, $fg, $Kg) : " $I$fg"));
    }
    function insert($R, array $Q)
    {
        return queries(
            "INSERT INTO " .
                table($R) .
                ($Q
                    ? " (" .
                        implode(", ", array_keys($Q)) .
                        ")\nVALUES (" .
                        implode(", ", $Q) .
                        ")"
                    : " DEFAULT VALUES") .
                $this->insertReturning($R),
        );
    }
    function insertReturning($R)
    {
        return "";
    }
    function insertUpdate($R, array $M, array $Wf)
    {
        return false;
    }
    function begin()
    {
        return queries("BEGIN");
    }
    function commit()
    {
        return queries("COMMIT");
    }
    function rollback()
    {
        return queries("ROLLBACK");
    }
    function slowQuery($I, $Dh) {}
    function convertSearch($v, array $X, array $l)
    {
        return $v;
    }
    function convertOperator($hf)
    {
        return $hf;
    }
    function value($X, array $l)
    {
        return method_exists($this->conn, "value")
            ? $this->conn->value($X, $l)
            : $X;
    }
    function quoteBinary($N)
    {
        return q($N);
    }
    function warnings() {}
    function tableHelp($D, $Qd = false) {}
    function hasCStyleEscapes()
    {
        return false;
    }
    function engines()
    {
        return [];
    }
    function supportsIndex(array $S)
    {
        return !is_view($S);
    }
    function checkConstraints($R)
    {
        return get_key_vals(
            "SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME
WHERE c.CONSTRAINT_SCHEMA = " .
                q($_GET["ns"] != "" ? $_GET["ns"] : DB) .
                "
AND t.TABLE_NAME = " .
                q($R) .
                "
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'",
            $this->conn,
        );
    }
    function allFields()
    {
        $K = [];
        foreach (
            get_rows(
                "SELECT TABLE_NAME AS tab, COLUMN_NAME AS field, IS_NULLABLE AS nullable, DATA_TYPE AS type, CHARACTER_MAXIMUM_LENGTH AS length" .
                    (JUSH == "sql" ? ", COLUMN_KEY = 'PRI' AS `primary`" : "") .
                    "
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = " .
                    q($_GET["ns"] != "" ? $_GET["ns"] : DB) .
                    "
ORDER BY TABLE_NAME, ORDINAL_POSITION",
                $this->conn,
            )
            as $L
        ) {
            $L["null"] = $L["nullable"] == "YES";
            $K[$L["tab"]][] = $L;
        }
        return $K;
    }
}
class Adminer
{
    static $Id;
    var $error = "";
    function name()
    {
        return "<a href='https://www.adminer.org/'" .
            target_blank() .
            " id='h1'><img src='" .
            h(
                preg_replace("~\\?.*~", "", ME) .
                    "?file=logo.png&version=5.2.1",
            ) .
            "' width='24' height='24' alt='' id='logo'>Adminer</a>";
    }
    function credentials()
    {
        return [SERVER, $_GET["username"], get_password()];
    }
    function connectSsl() {}
    function permanentLogin($g = false)
    {
        return password_file($g);
    }
    function bruteForceKey()
    {
        return $_SERVER["REMOTE_ADDR"];
    }
    function serverName($P)
    {
        return h($P);
    }
    function database()
    {
        return DB;
    }
    function databases($Oc = true)
    {
        return get_databases($Oc);
    }
    function pluginsLinks() {}
    function operators()
    {
        return driver()->operators;
    }
    function schemas()
    {
        return schemas();
    }
    function queryTimeout()
    {
        return 2;
    }
    function headers() {}
    function csp(array $vb)
    {
        return $vb;
    }
    function head($zb = null)
    {
        return true;
    }
    function css()
    {
        $K = [];
        foreach (["", "-dark"] as $Ge) {
            $n = "adminer$Ge.css";
            if (file_exists($n)) {
                $K[] = "$n?v=" . crc32(file_get_contents($n));
            }
        }
        return $K;
    }
    function loginForm()
    {
        echo "<table class='layout'>\n",
            adminer()->loginFormField(
                "driver",
                "<tr><th>" . "System" . "<td>",
                input_hidden("auth[driver]", "server") . "MySQL / MariaDB",
            ),
            adminer()->loginFormField(
                "server",
                "<tr><th>" . "Server" . "<td>",
                '<input name="auth[server]" value="' .
                    h(SERVER) .
                    '" title="hostname[:port]" placeholder="localhost" autocapitalize="off">',
            ),
            adminer()->loginFormField(
                "username",
                "<tr><th>" . "Username" . "<td>",
                '<input name="auth[username]" id="username" autofocus value="' .
                    h($_GET["username"]) .
                    '" autocomplete="username" autocapitalize="off">',
            ),
            adminer()->loginFormField(
                "password",
                "<tr><th>" . "Password" . "<td>",
                '<input type="password" name="auth[password]" autocomplete="current-password">',
            ),
            adminer()->loginFormField(
                "db",
                "<tr><th>" . "Database" . "<td>",
                '<input name="auth[db]" value="' .
                    h($_GET["db"]) .
                    '" autocapitalize="off">',
            ),
            "</table>\n",
            "<p><input type='submit' value='" . "Login" . "'>\n",
            checkbox(
                "auth[permanent]",
                1,
                $_COOKIE["adminer_permanent"],
                "Permanent login",
            ) . "\n";
    }
    function loginFormField($D, $md, $Y)
    {
        return $md . $Y . "\n";
    }
    function login($le, $G)
    {
        if ($G == "") {
            return sprintf(
                'Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',
                target_blank(),
            );
        }
        return true;
    }
    function tableName(array $oh)
    {
        return h($oh["Name"]);
    }
    function fieldName(array $l, $mf = 0)
    {
        $U = $l["full_type"];
        $fb = $l["comment"];
        return '<span title="' .
            h($U . ($fb != "" ? ($U ? ": " : "") . $fb : "")) .
            '">' .
            h($l["field"]) .
            "</span>";
    }
    function selectLinks(array $oh, $Q = "")
    {
        echo '<p class="links">';
        $ke = ["select" => "Select data"];
        if (support("table") || support("indexes")) {
            $ke["table"] = "Show structure";
        }
        $Qd = false;
        if (support("table")) {
            $Qd = is_view($oh);
            if ($Qd) {
                $ke["view"] = "Alter view";
            } else {
                $ke["create"] = "Alter table";
            }
        }
        if ($Q !== null) {
            $ke["edit"] = "New item";
        }
        $D = $oh["Name"];
        foreach ($ke as $y => $X) {
            echo " <a href='" .
                h(ME) .
                "$y=" .
                urlencode($D) .
                ($y == "edit" ? $Q : "") .
                "'" .
                bold(isset($_GET[$y])) .
                ">$X</a>";
        }
        echo doc_link([JUSH => driver()->tableHelp($D, $Qd)], "?"), "\n";
    }
    function foreignKeys($R)
    {
        return foreign_keys($R);
    }
    function backwardKeys($R, $nh)
    {
        return [];
    }
    function backwardKeysPrint(array $_a, array $L) {}
    function selectQuery($I, $ch, $Ec = false)
    {
        $K = "</p>\n";
        if (!$Ec && ($wi = driver()->warnings())) {
            $u = "warnings";
            $K =
                ", <a href='#$u'>" .
                "Warnings" .
                "</a>" .
                script("qsl('a').onclick = partial(toggle, '$u');", "") .
                "$K<div id='$u' class='hidden'>\n$wi</div>\n";
        }
        return "<p><code class='jush-" .
            JUSH .
            "'>" .
            h(str_replace("\n", " ", $I)) .
            "</code> <span class='time'>(" .
            format_time($ch) .
            ")</span>" .
            (support("sql")
                ? " <a href='" .
                    h(ME) .
                    "sql=" .
                    urlencode($I) .
                    "'>" .
                    "Edit" .
                    "</a>"
                : "") .
            $K;
    }
    function sqlCommandQuery($I)
    {
        return shorten_utf8(trim($I), 1000);
    }
    function sqlPrintAfter() {}
    function rowDescription($R)
    {
        return "";
    }
    function rowDescriptions(array $M, array $Rc)
    {
        return $M;
    }
    function selectLink($X, array $l) {}
    function selectVal($X, $_, array $l, $wf)
    {
        $K =
            $X === null
                ? "<i>NULL</i>"
                : (preg_match("~char|binary|boolean~", $l["type"]) &&
                !preg_match("~var~", $l["type"])
                    ? "<code>$X</code>"
                    : (preg_match("~json~", $l["type"])
                        ? "<code class='jush-js'>$X</code>"
                        : $X));
        if (preg_match("~blob|bytea|raw|file~", $l["type"]) && !is_utf8($X)) {
            $K =
                "<i>" .
                lang_format(["%d byte", "%d bytes"], strlen($wf)) .
                "</i>";
        }
        return $_
            ? "<a href='" .
                    h($_) .
                    "'" .
                    (is_url($_) ? target_blank() : "") .
                    ">$K</a>"
            : $K;
    }
    function editVal($X, array $l)
    {
        return $X;
    }
    function config()
    {
        return [];
    }
    function tableStructurePrint(array $m, $oh = null)
    {
        echo "<div class='scrollable'>\n",
            "<table class='nowrap odds'>\n",
            "<thead><tr><th>" .
                "Column" .
                "<td>" .
                "Type" .
                (support("comment") ? "<td>" . "Comment" : "") .
                "</thead>\n";
        $hh = driver()->structuredTypes();
        foreach ($m as $l) {
            echo "<tr><th>" . h($l["field"]);
            $U = h($l["full_type"]);
            $bb = h($l["collation"]);
            echo "<td><span title='$bb'>" .
            (in_array($U, (array) $hh["User types"])
                ? "<a href='" . h(ME . "type=" . urlencode($U)) . "'>$U</a>"
                : $U .
                    ($bb && isset($oh["Collation"]) && $bb != $oh["Collation"]
                        ? " $bb"
                        : "")) .
            "</span>",
                $l["null"] ? " <i>NULL</i>" : "",
                $l["auto_increment"] ? " <i>" . "Auto Increment" . "</i>" : "";
            $j = h($l["default"]);
            echo isset($l["default"])
            ? " <span title='" .
                "Default value" .
                "'>[<b>" .
                ($l["generated"]
                    ? "<code class='jush-" . JUSH . "'>$j</code>"
                    : $j) .
                "</b>]</span>"
            : "",
                support("comment") ? "<td>" . h($l["comment"]) : "",
                "\n";
        }
        echo "</table>\n", "</div>\n";
    }
    function tableIndexesPrint(array $x)
    {
        echo "<table>\n";
        foreach ($x as $D => $w) {
            ksort($w["columns"]);
            $Xf = [];
            foreach ($w["columns"] as $y => $X) {
                $Xf[] =
                    "<i>" .
                    h($X) .
                    "</i>" .
                    ($w["lengths"][$y] ? "(" . $w["lengths"][$y] . ")" : "") .
                    ($w["descs"][$y] ? " DESC" : "");
            }
            echo "<tr title='" .
                h($D) .
                "'><th>$w[type]<td>" .
                implode(", ", $Xf) .
                "\n";
        }
        echo "</table>\n";
    }
    function selectColumnsPrint(array $O, array $d)
    {
        print_fieldset("select", "Select", $O);
        $t = 0;
        $O[""] = [];
        foreach ($O as $y => $X) {
            $X = idx($_GET["columns"], $y, []);
            $c = select_input(
                " name='columns[$t][col]'",
                $d,
                $X["col"],
                $y !== "" ? "selectFieldChange" : "selectAddRow",
            );
            echo "<div>" .
                (driver()->functions || driver()->grouping
                    ? html_select(
                            "columns[$t][fun]",
                            [-1 => ""] +
                                array_filter([
                                    "Functions" => driver()->functions,
                                    "Aggregation" => driver()->grouping,
                                ]),
                            $X["fun"],
                        ) .
                        on_help(
                            "event.target.value && event.target.value.replace(/ |\$/, '(') + ')'",
                            1,
                        ) .
                        script(
                            "qsl('select').onchange = function () { helpClose();" .
                                ($y !== ""
                                    ? ""
                                    : " qsl('select, input', this.parentNode).onchange();") .
                                " };",
                            "",
                        ) .
                        "($c)"
                    : $c) .
                "</div>\n";
            $t++;
        }
        echo "</div></fieldset>\n";
    }
    function selectSearchPrint(array $Z, array $d, array $x)
    {
        print_fieldset("search", "Search", $Z);
        foreach ($x as $t => $w) {
            if ($w["type"] == "FULLTEXT") {
                echo "<div>(<i>" .
                implode("</i>, <i>", array_map("Adminer\h", $w["columns"])) .
                "</i>) AGAINST",
                    " <input type='search' name='fulltext[$t]' value='" .
                        h($_GET["fulltext"][$t]) .
                        "'>",
                    script("qsl('input').oninput = selectFieldChange;", ""),
                    checkbox(
                        "boolean[$t]",
                        1,
                        isset($_GET["boolean"][$t]),
                        "BOOL",
                    ),
                    "</div>\n";
            }
        }
        $Na = "this.parentNode.firstChild.onchange();";
        foreach (array_merge((array) $_GET["where"], [[]]) as $t => $X) {
            if (
                !$X ||
                ("$X[col]$X[val]" != "" &&
                    in_array($X["op"], adminer()->operators()))
            ) {
                echo "<div>" .
                select_input(
                    " name='where[$t][col]'",
                    $d,
                    $X["col"],
                    $X ? "selectFieldChange" : "selectAddRow",
                    "(" . "anywhere" . ")",
                ),
                    html_select(
                        "where[$t][op]",
                        adminer()->operators(),
                        $X["op"],
                        $Na,
                    ),
                    "<input type='search' name='where[$t][val]' value='" .
                        h($X["val"]) .
                        "'>",
                    script(
                        "mixin(qsl('input'), {oninput: function () { $Na }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",
                        "",
                    ),
                    "</div>\n";
            }
        }
        echo "</div></fieldset>\n";
    }
    function selectOrderPrint(array $mf, array $d, array $x)
    {
        print_fieldset("sort", "Sort", $mf);
        $t = 0;
        foreach ((array) $_GET["order"] as $y => $X) {
            if ($X != "") {
                echo "<div>" .
                select_input(" name='order[$t]'", $d, $X, "selectFieldChange"),
                    checkbox(
                        "desc[$t]",
                        1,
                        isset($_GET["desc"][$y]),
                        "descending",
                    ) . "</div>\n";
                $t++;
            }
        }
        echo "<div>" .
        select_input(" name='order[$t]'", $d, "", "selectAddRow"),
            checkbox("desc[$t]", 1, false, "descending") . "</div>\n",
            "</div></fieldset>\n";
    }
    function selectLimitPrint($z)
    {
        echo "<fieldset><legend>" . "Limit" . "</legend><div>",
            "<input type='number' name='limit' class='size' value='" .
                intval($z) .
                "'>",
            script("qsl('input').oninput = selectFieldChange;", ""),
            "</div></fieldset>\n";
    }
    function selectLengthPrint($Bh)
    {
        if ($Bh !== null) {
            echo "<fieldset><legend>" . "Text length" . "</legend><div>",
                "<input type='number' name='text_length' class='size' value='" .
                    h($Bh) .
                    "'>",
                "</div></fieldset>\n";
        }
    }
    function selectActionPrint(array $x)
    {
        echo "<fieldset><legend>" . "Action" . "</legend><div>",
            "<input type='submit' value='" . "Select" . "'>",
            " <span id='noindex' title='" . "Full table scan" . "'></span>",
            "<script" . nonce() . ">\n",
            "const indexColumns = ";
        $d = [];
        foreach ($x as $w) {
            $yb = reset($w["columns"]);
            if ($w["type"] != "FULLTEXT" && $yb) {
                $d[$yb] = 1;
            }
        }
        $d[""] = 1;
        foreach ($d as $y => $X) {
            json_row($y);
        }
        echo ";\n",
            "selectFieldChange.call(qs('#form')['select']);\n",
            "</script>\n",
            "</div></fieldset>\n";
    }
    function selectCommandPrint()
    {
        return !information_schema(DB);
    }
    function selectImportPrint()
    {
        return !information_schema(DB);
    }
    function selectEmailPrint(array $gc, array $d) {}
    function selectColumnsProcess(array $d, array $x)
    {
        $O = [];
        $s = [];
        foreach ((array) $_GET["columns"] as $y => $X) {
            if (
                $X["fun"] == "count" ||
                ($X["col"] != "" &&
                    (!$X["fun"] ||
                        in_array($X["fun"], driver()->functions) ||
                        in_array($X["fun"], driver()->grouping)))
            ) {
                $O[$y] = apply_sql_function(
                    $X["fun"],
                    $X["col"] != "" ? idf_escape($X["col"]) : "*",
                );
                if (!in_array($X["fun"], driver()->grouping)) {
                    $s[] = $O[$y];
                }
            }
        }
        return [$O, $s];
    }
    function selectSearchProcess(array $m, array $x)
    {
        $K = [];
        foreach ($x as $t => $w) {
            if ($w["type"] == "FULLTEXT" && $_GET["fulltext"][$t] != "") {
                $K[] =
                    "MATCH (" .
                    implode(
                        ", ",
                        array_map("Adminer\idf_escape", $w["columns"]),
                    ) .
                    ") AGAINST (" .
                    q($_GET["fulltext"][$t]) .
                    (isset($_GET["boolean"][$t]) ? " IN BOOLEAN MODE" : "") .
                    ")";
            }
        }
        foreach ((array) $_GET["where"] as $y => $X) {
            $Za = $X["col"];
            if (
                "$Za$X[val]" != "" &&
                in_array($X["op"], adminer()->operators())
            ) {
                $jb = [];
                foreach ($Za != "" ? [$Za => $m[$Za]] : $m as $D => $l) {
                    $Uf = "";
                    $ib = " $X[op]";
                    if (preg_match('~IN$~', $X["op"])) {
                        $zd = process_length($X["val"]);
                        $ib .= " " . ($zd != "" ? $zd : "(NULL)");
                    } elseif ($X["op"] == "SQL") {
                        $ib = " $X[val]";
                    } elseif (preg_match('~^(I?LIKE) %%$~', $X["op"], $B)) {
                        $ib =
                            " $B[1] " .
                            adminer()->processInput($l, "%$X[val]%");
                    } elseif ($X["op"] == "FIND_IN_SET") {
                        $Uf = "$X[op](" . q($X["val"]) . ", ";
                        $ib = ")";
                    } elseif (!preg_match('~NULL$~', $X["op"])) {
                        $ib .= " " . adminer()->processInput($l, $X["val"]);
                    }
                    if (
                        $Za != "" ||
                        (isset($l["privileges"]["where"]) &&
                            (preg_match(
                                "~^[-\d." .
                                    (preg_match('~IN$~', $X["op"]) ? "," : "") .
                                    ']+$~',
                                $X["val"],
                            ) ||
                                !preg_match(
                                    "~" . number_type() . "|bit~",
                                    $l["type"],
                                )) &&
                            (!preg_match("~[\x80-\xFF]~", $X["val"]) ||
                                preg_match(
                                    "~char|text|enum|set~",
                                    $l["type"],
                                )) &&
                            (!preg_match("~date|timestamp~", $l["type"]) ||
                                preg_match("~^\d+-\d+-\d+~", $X["val"])))
                    ) {
                        $jb[] =
                            $Uf .
                            driver()->convertSearch(idf_escape($D), $X, $l) .
                            $ib;
                    }
                }
                $K[] =
                    count($jb) == 1
                        ? $jb[0]
                        : ($jb
                            ? "(" . implode(" OR ", $jb) . ")"
                            : "1 = 0");
            }
        }
        return $K;
    }
    function selectOrderProcess(array $m, array $x)
    {
        $K = [];
        foreach ((array) $_GET["order"] as $y => $X) {
            if ($X != "") {
                $K[] =
                    (preg_match(
                        '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',
                        $X,
                    )
                        ? $X
                        : idf_escape($X)) .
                    (isset($_GET["desc"][$y]) ? " DESC" : "");
            }
        }
        return $K;
    }
    function selectLimitProcess()
    {
        return isset($_GET["limit"]) ? intval($_GET["limit"]) : 50;
    }
    function selectLengthProcess()
    {
        return isset($_GET["text_length"]) ? "$_GET[text_length]" : "100";
    }
    function selectEmailProcess(array $Z, array $Rc)
    {
        return false;
    }
    function selectQueryBuild(array $O, array $Z, array $s, array $mf, $z, $E)
    {
        return "";
    }
    function messageQuery($I, $Ch, $Ec = false)
    {
        restart_session();
        $od = &get_session("queries");
        if (!idx($od, $_GET["db"])) {
            $od[$_GET["db"]] = [];
        }
        if (strlen($I) > 1e6) {
            $I =
                preg_replace('~[\x80-\xFF]+$~', "", substr($I, 0, 1e6)) . "\n…";
        }
        $od[$_GET["db"]][] = [$I, time(), $Ch];
        $Zg = "sql-" . count($od[$_GET["db"]]);
        $K = "<a href='#$Zg' class='toggle'>" . "SQL command" . "</a>\n";
        if (!$Ec && ($wi = driver()->warnings())) {
            $u = "warnings-" . count($od[$_GET["db"]]);
            $K =
                "<a href='#$u' class='toggle'>" .
                "Warnings" .
                "</a>, $K<div id='$u' class='hidden'>\n$wi</div>\n";
        }
        return " <span class='time'>" .
            @date("H:i:s") .
            "</span>" .
            " $K<div id='$Zg' class='hidden'><pre><code class='jush-" .
            JUSH .
            "'>" .
            shorten_utf8($I, 1000) .
            "</code></pre>" .
            ($Ch ? " <span class='time'>($Ch)</span>" : "") .
            (support("sql")
                ? '<p><a href="' .
                    h(
                        str_replace(
                            "db=" . urlencode(DB),
                            "db=" . urlencode($_GET["db"]),
                            ME,
                        ) .
                            "sql=&history=" .
                            (count($od[$_GET["db"]]) - 1),
                    ) .
                    '">' .
                    "Edit" .
                    "</a>"
                : "") .
            "</div>";
    }
    function editRowPrint($R, array $m, $L, $fi) {}
    function editFunctions(array $l)
    {
        $K = $l["null"] ? "NULL/" : "";
        $fi = isset($_GET["select"]) || where($_GET);
        foreach (
            [driver()->insertFunctions, driver()->editFunctions]
            as $y => $Xc
        ) {
            if (!$y || (!isset($_GET["call"]) && $fi)) {
                foreach ($Xc as $If => $X) {
                    if (!$If || preg_match("~$If~", $l["type"])) {
                        $K .= "/$X";
                    }
                }
            }
            if (
                $y &&
                $Xc &&
                !preg_match("~set|blob|bytea|raw|file|bool~", $l["type"])
            ) {
                $K .= "/SQL";
            }
        }
        if ($l["auto_increment"] && !$fi) {
            $K = "Auto Increment";
        }
        return explode("/", $K);
    }
    function editInput($R, array $l, $ua, $Y)
    {
        if ($l["type"] == "enum") {
            return (isset($_GET["select"])
                ? "<label><input type='radio'$ua value='-1' checked><i>" .
                    "original" .
                    "</i></label> "
                : "") .
                ($l["null"]
                    ? "<label><input type='radio'$ua value=''" .
                        ($Y !== null || isset($_GET["select"])
                            ? ""
                            : " checked") .
                        "><i>NULL</i></label> "
                    : "") .
                enum_input("radio", $ua, $l, $Y, $Y === 0 ? 0 : null);
        }
        return "";
    }
    function editHint($R, array $l, $Y)
    {
        return "";
    }
    function processInput(array $l, $Y, $r = "")
    {
        if ($r == "SQL") {
            return $Y;
        }
        $D = $l["field"];
        $K = q($Y);
        if (preg_match('~^(now|getdate|uuid)$~', $r)) {
            $K = "$r()";
        } elseif (preg_match('~^current_(date|timestamp)$~', $r)) {
            $K = $r;
        } elseif (preg_match('~^([+-]|\|\|)$~', $r)) {
            $K = idf_escape($D) . " $r $K";
        } elseif (preg_match('~^[+-] interval$~', $r)) {
            $K =
                idf_escape($D) .
                " $r " .
                (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $Y) ? $Y : $K);
        } elseif (preg_match('~^(addtime|subtime|concat)$~', $r)) {
            $K = "$r(" . idf_escape($D) . ", $K)";
        } elseif (preg_match('~^(md5|sha1|password|encrypt)$~', $r)) {
            $K = "$r($K)";
        }
        return unconvert_field($l, $K);
    }
    function dumpOutput()
    {
        $K = ["text" => "open", "file" => "save"];
        if (function_exists("gzencode")) {
            $K["gz"] = "gzip";
        }
        return $K;
    }
    function dumpFormat()
    {
        return (support("dump") ? ["sql" => "SQL"] : []) + [
            "csv" => "CSV,",
            "csv;" => "CSV;",
            "tsv" => "TSV",
        ];
    }
    function dumpDatabase($i) {}
    function dumpTable($R, $ih, $Qd = 0)
    {
        if ($_POST["format"] != "sql") {
            echo "\xef\xbb\xbf";
            if ($ih) {
                dump_csv(array_keys(fields($R)));
            }
        } else {
            if ($Qd == 2) {
                $m = [];
                foreach (fields($R) as $D => $l) {
                    $m[] = idf_escape($D) . " $l[full_type]";
                }
                $g =
                    "CREATE TABLE " .
                    table($R) .
                    " (" .
                    implode(", ", $m) .
                    ")";
            } else {
                $g = create_sql($R, $_POST["auto_increment"], $ih);
            }
            set_utf8mb4($g);
            if ($ih && $g) {
                if ($ih == "DROP+CREATE" || $Qd == 1) {
                    echo "DROP " .
                        ($Qd == 2 ? "VIEW" : "TABLE") .
                        " IF EXISTS " .
                        table($R) .
                        ";\n";
                }
                if ($Qd == 1) {
                    $g = remove_definer($g);
                }
                echo "$g;\n\n";
            }
        }
    }
    function dumpData($R, $ih, $I)
    {
        if ($ih) {
            $se = JUSH == "sqlite" ? 0 : 1048576;
            $m = [];
            $wd = false;
            if ($_POST["format"] == "sql") {
                if ($ih == "TRUNCATE+INSERT") {
                    echo truncate_sql($R) . ";\n";
                }
                $m = fields($R);
                if (JUSH == "mssql") {
                    foreach ($m as $l) {
                        if ($l["auto_increment"]) {
                            echo "SET IDENTITY_INSERT " . table($R) . " ON;\n";
                            $wd = true;
                            break;
                        }
                    }
                }
            }
            $J = connection()->query($I, 1);
            if ($J) {
                $Gd = "";
                $Ja = "";
                $Ud = [];
                $Yc = [];
                $kh = "";
                $Hc = $R != "" ? "fetch_assoc" : "fetch_row";
                $rb = 0;
                while ($L = $J->$Hc()) {
                    if (!$Ud) {
                        $oi = [];
                        foreach ($L as $X) {
                            $l = $J->fetch_field();
                            if (idx($m[$l->name], "generated")) {
                                $Yc[$l->name] = true;
                                continue;
                            }
                            $Ud[] = $l->name;
                            $y = idf_escape($l->name);
                            $oi[] = "$y = VALUES($y)";
                        }
                        $kh =
                            ($ih == "INSERT+UPDATE"
                                ? "\nON DUPLICATE KEY UPDATE " .
                                    implode(", ", $oi)
                                : "") . ";\n";
                    }
                    if ($_POST["format"] != "sql") {
                        if ($ih == "table") {
                            dump_csv($Ud);
                            $ih = "INSERT";
                        }
                        dump_csv($L);
                    } else {
                        if (!$Gd) {
                            $Gd =
                                "INSERT INTO " .
                                table($R) .
                                " (" .
                                implode(
                                    ", ",
                                    array_map("Adminer\idf_escape", $Ud),
                                ) .
                                ") VALUES";
                        }
                        foreach ($L as $y => $X) {
                            if ($Yc[$y]) {
                                unset($L[$y]);
                                continue;
                            }
                            $l = $m[$y];
                            $L[$y] =
                                $X !== null
                                    ? unconvert_field(
                                        $l,
                                        preg_match(number_type(), $l["type"]) &&
                                        !preg_match("~\[~", $l["full_type"]) &&
                                        is_numeric($X)
                                            ? $X
                                            : q($X === false ? 0 : $X),
                                    )
                                    : "NULL";
                        }
                        $N =
                            ($se ? "\n" : " ") . "(" . implode(",\t", $L) . ")";
                        if (!$Ja) {
                            $Ja = $Gd . $N;
                        } elseif (
                            JUSH == "mssql"
                                ? $rb % 1000 != 0
                                : strlen($Ja) + 4 + strlen($N) + strlen($kh) <
                                    $se
                        ) {
                            $Ja .= ",$N";
                        } else {
                            echo $Ja . $kh;
                            $Ja = $Gd . $N;
                        }
                    }
                    $rb++;
                }
                if ($Ja) {
                    echo $Ja . $kh;
                }
            } elseif ($_POST["format"] == "sql") {
                echo "-- " . str_replace("\n", " ", connection()->error) . "\n";
            }
            if ($wd) {
                echo "SET IDENTITY_INSERT " . table($R) . " OFF;\n";
            }
        }
    }
    function dumpFilename($vd)
    {
        return friendly_url(
            $vd != "" ? $vd : (SERVER != "" ? SERVER : "localhost"),
        );
    }
    function dumpHeaders($vd, $Ie = false)
    {
        $yf = $_POST["output"];
        $_c = preg_match("~sql~", $_POST["format"])
            ? "sql"
            : ($Ie
                ? "tar"
                : "csv");
        header(
            "Content-Type: " .
                ($yf == "gz"
                    ? "application/x-gzip"
                    : ($_c == "tar"
                        ? "application/x-tar"
                        : ($_c == "sql" || $yf != "file"
                                ? "text/plain"
                                : "text/csv") . "; charset=utf-8")),
        );
        if ($yf == "gz") {
            ob_start(function ($gh) {
                return gzencode($gh);
            }, 1e6);
        }
        return $_c;
    }
    function dumpFooter()
    {
        if ($_POST["format"] == "sql") {
            echo "-- " . gmdate("Y-m-d H:i:s e") . "\n";
        }
    }
    function importServerPath()
    {
        return "adminer.sql";
    }
    function homepage()
    {
        echo '<p class="links">' .
        ($_GET["ns"] == "" && support("database")
            ? '<a href="' . h(ME) . 'database=">' . "Alter database" . "</a>\n"
            : ""),
            support("scheme")
                ? "<a href='" .
                    h(ME) .
                    "scheme='>" .
                    ($_GET["ns"] != "" ? "Alter schema" : "Create schema") .
                    "</a>\n"
                : "",
            $_GET["ns"] !== ""
                ? '<a href="' .
                    h(ME) .
                    'schema=">' .
                    "Database schema" .
                    "</a>\n"
                : "",
            support("privileges")
                ? "<a href='" .
                    h(ME) .
                    "privileges='>" .
                    "Privileges" .
                    "</a>\n"
                : "";
        return true;
    }
    function navigation($Fe)
    {
        echo "<h1>" . adminer()->name() . " <span class='version'>" . VERSION;
        $Qe = $_COOKIE["adminer_version"];
        echo " <a href='https://www.adminer.org/#download'" .
        target_blank() .
        " id='version'>" .
        (version_compare(VERSION, $Qe) < 0 ? h($Qe) : "") .
        "</a>",
            "</span></h1>\n";
        if ($Fe == "auth") {
            $yf = "";
            foreach ((array) $_SESSION["pwds"] as $qi => $Mg) {
                foreach ($Mg as $P => $mi) {
                    $D = h(get_setting("vendor-$qi-$P") ?: get_driver($qi));
                    foreach ($mi as $V => $G) {
                        if ($G !== null) {
                            $Eb = $_SESSION["db"][$qi][$P][$V];
                            foreach ($Eb ? array_keys($Eb) : [""] as $i) {
                                $yf .=
                                    "<li><a href='" .
                                    h(auth_url($qi, $P, $V, $i)) .
                                    "'>($D) " .
                                    h(
                                        $V .
                                            ($P != ""
                                                ? "@" .
                                                    adminer()->serverName($P)
                                                : "") .
                                            ($i != "" ? " - $i" : ""),
                                    ) .
                                    "</a>\n";
                            }
                        }
                    }
                }
            }
            if ($yf) {
                echo "<ul id='logins'>\n$yf</ul>\n" .
                    script(
                        "mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});",
                    );
            }
        } else {
            $T = [];
            if ($_GET["ns"] !== "" && !$Fe && DB != "") {
                connection()->select_db(DB);
                $T = table_status("", true);
            }
            adminer()->syntaxHighlighting($T);
            adminer()->databasesPrint($Fe);
            $fa = [];
            if (DB == "" || !$Fe) {
                if (support("sql")) {
                    $fa[] =
                        "<a href='" .
                        h(ME) .
                        "sql='" .
                        bold(isset($_GET["sql"]) && !isset($_GET["import"])) .
                        ">" .
                        "SQL command" .
                        "</a>";
                    $fa[] =
                        "<a href='" .
                        h(ME) .
                        "import='" .
                        bold(isset($_GET["import"])) .
                        ">" .
                        "Import" .
                        "</a>";
                }
                $fa[] =
                    "<a href='" .
                    h(ME) .
                    "dump=" .
                    urlencode(
                        isset($_GET["table"])
                            ? $_GET["table"]
                            : $_GET["select"],
                    ) .
                    "' id='dump'" .
                    bold(isset($_GET["dump"])) .
                    ">" .
                    "Export" .
                    "</a>";
            }
            $_d = $_GET["ns"] !== "" && !$Fe && DB != "";
            if ($_d) {
                $fa[] =
                    '<a href="' .
                    h(ME) .
                    'create="' .
                    bold($_GET["create"] === "") .
                    ">" .
                    "Create table" .
                    "</a>";
            }
            echo $fa ? "<p class='links'>\n" . implode("\n", $fa) . "\n" : "";
            if ($_d) {
                if ($T) {
                    adminer()->tablesPrint($T);
                } else {
                    echo "<p class='message'>" . "No tables." . "</p>\n";
                }
            }
        }
    }
    function syntaxHighlighting(array $T)
    {
        echo script_src(
            preg_replace("~\\?.*~", "", ME) . "?file=jush.js&version=5.2.1",
            true,
        );
        if (support("sql")) {
            echo "<script" . nonce() . ">\n";
            if ($T) {
                $ke = [];
                foreach ($T as $R => $U) {
                    $ke[] = preg_quote($R, "/");
                }
                echo "var jushLinks = { " .
                    JUSH .
                    ": [ '" .
                    js_escape(ME) .
                    (support("table") ? "table=" : "select=") .
                    "\$&', /\\b(" .
                    implode("|", $ke) .
                    ")\\b/g ] };\n";
                foreach (["bac", "bra", "sqlite_quo", "mssql_bra"] as $X) {
                    echo "jushLinks.$X = jushLinks." . JUSH . ";\n";
                }
                if (
                    isset($_GET["sql"]) ||
                    isset($_GET["trigger"]) ||
                    isset($_GET["check"])
                ) {
                    $th = array_fill_keys(array_keys($T), []);
                    foreach (driver()->allFields() as $R => $m) {
                        foreach ($m as $l) {
                            $th[$R][] = $l["field"];
                        }
                    }
                    echo "addEventListener('DOMContentLoaded', () => { autocompleter = jush.autocompleteSql('" .
                        idf_escape("") .
                        "', " .
                        json_encode($th) .
                        "); });\n";
                }
            }
            echo "</script>\n";
        }
        echo script(
            "syntaxHighlighting('" .
                preg_replace(
                    "~^(\d\.?\d).*~s",
                    '\1',
                    connection()->server_info,
                ) .
                "', '" .
                connection()->flavor .
                "');",
        );
    }
    function databasesPrint($Fe)
    {
        $h = adminer()->databases();
        if (DB && $h && !in_array(DB, $h)) {
            array_unshift($h, DB);
        }
        echo "<form action=''>\n<p id='dbs'>\n";
        hidden_fields_get();
        $Cb = script(
            "mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});",
        );
        echo "<label title='" .
        "Database" .
        "'>" .
        "DB" .
        ": " .
        ($h
            ? html_select("db", ["" => ""] + $h, DB) . $Cb
            : "<input name='db' value='" .
                h(DB) .
                "' autocapitalize='off' size='19'>\n") .
        "</label>",
            "<input type='submit' value='" .
                "Use" .
                "'" .
                ($h ? " class='hidden'" : "") .
                ">\n";
        foreach (["import", "sql", "schema", "dump", "privileges"] as $X) {
            if (isset($_GET[$X])) {
                echo input_hidden($X);
                break;
            }
        }
        echo "</p></form>\n";
    }
    function tablesPrint(array $T)
    {
        echo "<ul id='tables'>" .
            script(
                "mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});",
            );
        foreach ($T as $R => $dh) {
            $R = "$R";
            $D = adminer()->tableName($dh);
            if ($D != "") {
                echo '<li><a href="' .
                h(ME) .
                "select=" .
                urlencode($R) .
                '"' .
                bold($_GET["select"] == $R || $_GET["edit"] == $R, "select") .
                " title='" .
                "Select data" .
                "'>" .
                "select" .
                "</a> ",
                    (support("table") || support("indexes")
                        ? '<a href="' .
                            h(ME) .
                            "table=" .
                            urlencode($R) .
                            '"' .
                            bold(
                                in_array($R, [
                                    $_GET["table"],
                                    $_GET["create"],
                                    $_GET["indexes"],
                                    $_GET["foreign"],
                                    $_GET["trigger"],
                                    $_GET["check"],
                                    $_GET["view"],
                                ]),
                                is_view($dh) ? "view" : "structure",
                            ) .
                            " title='" .
                            "Show structure" .
                            "'>$D</a>"
                        : "<span>$D</span>") . "\n";
            }
        }
        echo "</ul>\n";
    }
}
class Plugins
{
    private static $pa = [
        "dumpFormat" => true,
        "dumpOutput" => true,
        "editRowPrint" => true,
        "editFunctions" => true,
        "config" => true,
    ];
    var $plugins;
    var $error = "";
    private $hooks = [];
    function __construct($Nf)
    {
        if ($Nf === null) {
            $Nf = [];
            $Da = "adminer-plugins";
            if (is_dir($Da)) {
                foreach (glob("$Da/*.php") as $n) {
                    $Ad = include_once "./$n";
                }
            }
            $nd =
                " href='https://www.adminer.org/plugins/#use'" . target_blank();
            if (file_exists("$Da.php")) {
                $Ad = include_once "./$Da.php";
                if (is_array($Ad)) {
                    foreach ($Ad as $Mf) {
                        $Nf[get_class($Mf)] = $Mf;
                    }
                } else {
                    $this->error .=
                        sprintf(
                            "%s must <a%s>return an array</a>.",
                            "<b>$Da.php</b>",
                            $nd,
                        ) . "<br>";
                }
            }
            foreach (get_declared_classes() as $Wa) {
                if (!$Nf[$Wa] && preg_match("~^Adminer\w~i", $Wa)) {
                    $og = new \ReflectionClass($Wa);
                    $lb = $og->getConstructor();
                    if ($lb && $lb->getNumberOfRequiredParameters()) {
                        $this->error .=
                            sprintf(
                                "<a%s>Configure</a> %s in %s.",
                                $nd,
                                "<b>$Wa</b>",
                                "<b>$Da.php</b>",
                            ) . "<br>";
                    } else {
                        $Nf[$Wa] = new $Wa();
                    }
                }
            }
        }
        $this->plugins = $Nf;
        $ga = new Adminer();
        $Nf[] = $ga;
        $og = new \ReflectionObject($ga);
        foreach ($og->getMethods() as $De) {
            foreach ($Nf as $Mf) {
                $D = $De->getName();
                if (method_exists($Mf, $D)) {
                    $this->hooks[$D][] = $Mf;
                }
            }
        }
    }
    function __call($D, array $F)
    {
        $qa = [];
        foreach ($F as $y => $X) {
            $qa[] = &$F[$y];
        }
        $K = null;
        foreach ($this->hooks[$D] as $Mf) {
            $Y = call_user_func_array([$Mf, $D], $qa);
            if ($Y !== null) {
                if (!self::$pa[$D]) {
                    return $Y;
                }
                $K = $Y + (array) $K;
            }
        }
        return $K;
    }
}
abstract class Plugin
{
    protected $translations = [];
    function description()
    {
        return $this->lang("");
    }
    function screenshot()
    {
        return "";
    }
    protected function lang($v, $Ve = null)
    {
        $qa = func_get_args();
        $qa[0] = idx($this->translations[LANG], $v) ?: $v;
        return call_user_func_array("Adminer\lang_format", $qa);
    }
}
Adminer::$Id = function_exists("adminer_object")
    ? adminer_object()
    : (is_dir("adminer-plugins") || file_exists("adminer-plugins.php")
        ? new Plugins(null)
        : new Adminer());
SqlDriver::$Ub = ["server" => "MySQL / MariaDB"] + SqlDriver::$Ub;
if (!defined("Adminer\DRIVER")) {
    define("Adminer\DRIVER", "server");
    if (extension_loaded("mysqli") && $_GET["ext"] != "pdo") {
        class Db extends \MySQLi
        {
            static $Id;
            var $extension = "MySQLi",
                $flavor = "";
            function __construct()
            {
                parent::init();
            }
            function attach($P, $V, $G)
            {
                mysqli_report(MYSQLI_REPORT_OFF);
                [$rd, $Of] = explode(":", $P, 2);
                $bh = adminer()->connectSsl();
                if ($bh) {
                    $this->ssl_set($bh["key"], $bh["cert"], $bh["ca"], "", "");
                }
                $K = @$this->real_connect(
                    $P != "" ? $rd : ini_get("mysqli.default_host"),
                    $P . $V != "" ? $V : ini_get("mysqli.default_user"),
                    $P . $V . $G != "" ? $G : ini_get("mysqli.default_pw"),
                    null,
                    is_numeric($Of)
                        ? intval($Of)
                        : ini_get("mysqli.default_port"),
                    is_numeric($Of) ? $Of : null,
                    $bh ? ($bh["verify"] !== false ? 2048 : 64) : 0,
                );
                $this->options(MYSQLI_OPT_LOCAL_INFILE, false);
                return $K ? "" : $this->error;
            }
            function set_charset($Pa)
            {
                if (parent::set_charset($Pa)) {
                    return true;
                }
                parent::set_charset("utf8");
                return $this->query("SET NAMES $Pa");
            }
            function next_result()
            {
                return self::more_results() && parent::next_result();
            }
            function quote($gh)
            {
                return "'" . $this->escape_string($gh) . "'";
            }
        }
    } elseif (
        extension_loaded("mysql") &&
        !(
            (ini_bool("sql.safe_mode") ||
                ini_bool("mysql.allow_local_infile")) &&
            extension_loaded("pdo_mysql")
        )
    ) {
        class Db extends SqlDb
        {
            private $link;
            function attach($P, $V, $G)
            {
                if (ini_bool("mysql.allow_local_infile")) {
                    return sprintf(
                        "Disable %s or enable %s or %s extensions.",
                        "'mysql.allow_local_infile'",
                        "MySQLi",
                        "PDO_MySQL",
                    );
                }
                $this->link = @mysql_connect(
                    $P != "" ? $P : ini_get("mysql.default_host"),
                    "$P$V" != "" ? $V : ini_get("mysql.default_user"),
                    "$P$V$G" != "" ? $G : ini_get("mysql.default_password"),
                    true,
                    131072,
                );
                if (!$this->link) {
                    return mysql_error();
                }
                $this->server_info = mysql_get_server_info($this->link);
                return "";
            }
            function set_charset($Pa)
            {
                if (function_exists("mysql_set_charset")) {
                    if (mysql_set_charset($Pa, $this->link)) {
                        return true;
                    }
                    mysql_set_charset("utf8", $this->link);
                }
                return $this->query("SET NAMES $Pa");
            }
            function quote($gh)
            {
                return "'" . mysql_real_escape_string($gh, $this->link) . "'";
            }
            function select_db($Bb)
            {
                return mysql_select_db($Bb, $this->link);
            }
            function query($I, $Yh = false)
            {
                $J = @$Yh
                    ? mysql_unbuffered_query($I, $this->link)
                    : mysql_query($I, $this->link);
                $this->error = "";
                if (!$J) {
                    $this->errno = mysql_errno($this->link);
                    $this->error = mysql_error($this->link);
                    return false;
                }
                if ($J === true) {
                    $this->affected_rows = mysql_affected_rows($this->link);
                    $this->info = mysql_info($this->link);
                    return true;
                }
                return new Result($J);
            }
        }
        class Result
        {
            var $num_rows;
            private $result;
            private $offset = 0;
            function __construct($J)
            {
                $this->result = $J;
                $this->num_rows = mysql_num_rows($J);
            }
            function fetch_assoc()
            {
                return mysql_fetch_assoc($this->result);
            }
            function fetch_row()
            {
                return mysql_fetch_row($this->result);
            }
            function fetch_field()
            {
                $K = mysql_fetch_field($this->result, $this->offset++);
                $K->orgtable = $K->table;
                $K->charsetnr = $K->blob ? 63 : 0;
                return $K;
            }
            function __destruct()
            {
                mysql_free_result($this->result);
            }
        }
    } elseif (extension_loaded("pdo_mysql")) {
        class Db extends PdoDb
        {
            var $extension = "PDO_MySQL";
            function attach($P, $V, $G)
            {
                $kf = [\PDO::MYSQL_ATTR_LOCAL_INFILE => false];
                $bh = adminer()->connectSsl();
                if ($bh) {
                    if ($bh["key"]) {
                        $kf[\PDO::MYSQL_ATTR_SSL_KEY] = $bh["key"];
                    }
                    if ($bh["cert"]) {
                        $kf[\PDO::MYSQL_ATTR_SSL_CERT] = $bh["cert"];
                    }
                    if ($bh["ca"]) {
                        $kf[\PDO::MYSQL_ATTR_SSL_CA] = $bh["ca"];
                    }
                    if (isset($bh["verify"])) {
                        $kf[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] =
                            $bh["verify"];
                    }
                }
                return $this->dsn(
                    "mysql:charset=utf8;host=" .
                        str_replace(
                            ":",
                            ";unix_socket=",
                            preg_replace("~:(\d)~", ';port=\1', $P),
                        ),
                    $V,
                    $G,
                    $kf,
                );
            }
            function set_charset($Pa)
            {
                return $this->query("SET NAMES $Pa");
            }
            function select_db($Bb)
            {
                return $this->query("USE " . idf_escape($Bb));
            }
            function query($I, $Yh = false)
            {
                $this->pdo->setAttribute(
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                    !$Yh,
                );
                return parent::query($I, $Yh);
            }
        }
    }
    class Driver extends SqlDriver
    {
        static $Bc = ["MySQLi", "MySQL", "PDO_MySQL"];
        static $Sd = "sql";
        var $unsigned = ["unsigned", "zerofill", "unsigned zerofill"];
        var $operators = [
            "=",
            "<",
            ">",
            "<=",
            ">=",
            "!=",
            "LIKE",
            "LIKE %%",
            "REGEXP",
            "IN",
            "FIND_IN_SET",
            "IS NULL",
            "NOT LIKE",
            "NOT REGEXP",
            "NOT IN",
            "IS NOT NULL",
            "SQL",
        ];
        var $functions = [
            "char_length",
            "date",
            "from_unixtime",
            "lower",
            "round",
            "floor",
            "ceil",
            "sec_to_time",
            "time_to_sec",
            "upper",
        ];
        var $grouping = [
            "avg",
            "count",
            "count distinct",
            "group_concat",
            "max",
            "min",
            "sum",
        ];
        static function connect($P, $V, $G)
        {
            $e = parent::connect($P, $V, $G);
            if (is_string($e)) {
                if (
                    function_exists("iconv") &&
                    !is_utf8($e) &&
                    strlen($N = iconv("windows-1250", "utf-8", $e)) > strlen($e)
                ) {
                    $e = $N;
                }
                return $e;
            }
            $e->set_charset(charset($e));
            $e->query("SET sql_quote_show_create = 1, autocommit = 1");
            $e->flavor = preg_match("~MariaDB~", $e->server_info)
                ? "maria"
                : "mysql";
            add_driver(DRIVER, $e->flavor == "maria" ? "MariaDB" : "MySQL");
            return $e;
        }
        function __construct(Db $e)
        {
            parent::__construct($e);
            $this->types = [
                "Numbers" => [
                    "tinyint" => 3,
                    "smallint" => 5,
                    "mediumint" => 8,
                    "int" => 10,
                    "bigint" => 20,
                    "decimal" => 66,
                    "float" => 12,
                    "double" => 21,
                ],
                "Date and time" => [
                    "date" => 10,
                    "datetime" => 19,
                    "timestamp" => 19,
                    "time" => 10,
                    "year" => 4,
                ],
                "Strings" => [
                    "char" => 255,
                    "varchar" => 65535,
                    "tinytext" => 255,
                    "text" => 65535,
                    "mediumtext" => 16777215,
                    "longtext" => 4294967295,
                ],
                "Lists" => ["enum" => 65535, "set" => 64],
                "Binary" => [
                    "bit" => 20,
                    "binary" => 255,
                    "varbinary" => 65535,
                    "tinyblob" => 255,
                    "blob" => 65535,
                    "mediumblob" => 16777215,
                    "longblob" => 4294967295,
                ],
                "Geometry" => [
                    "geometry" => 0,
                    "point" => 0,
                    "linestring" => 0,
                    "polygon" => 0,
                    "multipoint" => 0,
                    "multilinestring" => 0,
                    "multipolygon" => 0,
                    "geometrycollection" => 0,
                ],
            ];
            $this->insertFunctions = [
                "char" => "md5/sha1/password/encrypt/uuid",
                "binary" => "md5/sha1",
                "date|time" => "now",
            ];
            $this->editFunctions = [
                number_type() => "+/-",
                "date" => "+ interval/- interval",
                "time" => "addtime/subtime",
                "char|text" => "concat",
            ];
            if (min_version("5.7.8", 10.2, $e)) {
                $this->types["Strings"]["json"] = 4294967295;
            }
            if (min_version("", 10.7, $e)) {
                $this->types["Strings"]["uuid"] = 128;
                $this->insertFunctions["uuid"] = "uuid";
            }
            if (min_version(9, "", $e)) {
                $this->types["Numbers"]["vector"] = 16383;
                $this->insertFunctions["vector"] = "string_to_vector";
            }
            if (min_version(5.7, 10.2, $e)) {
                $this->generated = ["STORED", "VIRTUAL"];
            }
        }
        function unconvertFunction(array $l)
        {
            return preg_match("~binary~", $l["type"])
                ? "<code class='jush-sql'>UNHEX</code>"
                : ($l["type"] == "bit"
                    ? doc_link(
                        ["sql" => "bit-value-literals.html"],
                        "<code>b''</code>",
                    )
                    : (preg_match(
                        "~geometry|point|linestring|polygon~",
                        $l["type"],
                    )
                        ? "<code class='jush-sql'>GeomFromText</code>"
                        : ""));
        }
        function insert($R, array $Q)
        {
            return $Q
                ? parent::insert($R, $Q)
                : queries("INSERT INTO " . table($R) . " ()\nVALUES ()");
        }
        function insertUpdate($R, array $M, array $Wf)
        {
            $d = array_keys(reset($M));
            $Uf =
                "INSERT INTO " .
                table($R) .
                " (" .
                implode(", ", $d) .
                ") VALUES\n";
            $oi = [];
            foreach ($d as $y) {
                $oi[$y] = "$y = VALUES($y)";
            }
            $kh = "\nON DUPLICATE KEY UPDATE " . implode(", ", $oi);
            $oi = [];
            $ge = 0;
            foreach ($M as $Q) {
                $Y = "(" . implode(", ", $Q) . ")";
                if ($oi && strlen($Uf) + $ge + strlen($Y) + strlen($kh) > 1e6) {
                    if (!queries($Uf . implode(",\n", $oi) . $kh)) {
                        return false;
                    }
                    $oi = [];
                    $ge = 0;
                }
                $oi[] = $Y;
                $ge += strlen($Y) + 2;
            }
            return queries($Uf . implode(",\n", $oi) . $kh);
        }
        function slowQuery($I, $Dh)
        {
            if (min_version("5.7.8", "10.1.2")) {
                if ($this->conn->flavor == "maria") {
                    return "SET STATEMENT max_statement_time=$Dh FOR $I";
                } elseif (preg_match("~^(SELECT\b)(.+)~is", $I, $B)) {
                    return "$B[1] /*+ MAX_EXECUTION_TIME(" .
                        $Dh * 1000 .
                        ") */ $B[2]";
                }
            }
        }
        function convertSearch($v, array $X, array $l)
        {
            return preg_match("~char|text|enum|set~", $l["type"]) &&
                !preg_match("~^utf8~", $l["collation"]) &&
                preg_match('~[\x80-\xFF]~', $X["val"])
                ? "CONVERT($v USING " . charset($this->conn) . ")"
                : $v;
        }
        function warnings()
        {
            $J = $this->conn->query("SHOW WARNINGS");
            if ($J && $J->num_rows) {
                ob_start();
                print_select_result($J);
                return ob_get_clean();
            }
        }
        function tableHelp($D, $Qd = false)
        {
            $ne = $this->conn->flavor == "maria";
            if (information_schema(DB)) {
                return strtolower(
                    "information-schema-" .
                        ($ne
                            ? "$D-table/"
                            : str_replace("_", "-", $D) . "-table.html"),
                );
            }
            if (DB == "mysql") {
                return $ne ? "mysql$D-table/" : "system-schema.html";
            }
        }
        function hasCStyleEscapes()
        {
            static $Ka;
            if ($Ka === null) {
                $ah = get_val("SHOW VARIABLES LIKE 'sql_mode'", 1, $this->conn);
                $Ka = strpos($ah, "NO_BACKSLASH_ESCAPES") === false;
            }
            return $Ka;
        }
        function engines()
        {
            $K = [];
            foreach (get_rows("SHOW ENGINES") as $L) {
                if (preg_match("~YES|DEFAULT~", $L["Support"])) {
                    $K[] = $L["Engine"];
                }
            }
            return $K;
        }
    }
    function idf_escape($v)
    {
        return "`" . str_replace("`", "``", $v) . "`";
    }
    function table($v)
    {
        return idf_escape($v);
    }
    function get_databases($Oc)
    {
        $K = get_session("dbs");
        if ($K === null) {
            $I =
                "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";
            $K = $Oc ? slow_query($I) : get_vals($I);
            restart_session();
            set_session("dbs", $K);
            stop_session();
        }
        return $K;
    }
    function limit($I, $Z, $z, $Xe = 0, $Kg = " ")
    {
        return " $I$Z" .
            ($z ? $Kg . "LIMIT $z" . ($Xe ? " OFFSET $Xe" : "") : "");
    }
    function limit1($R, $I, $Z, $Kg = "\n")
    {
        return limit($I, $Z, 1, 0, $Kg);
    }
    function db_collation($i, array $b)
    {
        $K = null;
        $g = get_val("SHOW CREATE DATABASE " . idf_escape($i), 1);
        if (preg_match("~ COLLATE ([^ ]+)~", $g, $B)) {
            $K = $B[1];
        } elseif (preg_match("~ CHARACTER SET ([^ ]+)~", $g, $B)) {
            $K = $b[$B[1]][-1];
        }
        return $K;
    }
    function logged_user()
    {
        return get_val("SELECT USER()");
    }
    function tables_list()
    {
        return get_key_vals(
            "SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME",
        );
    }
    function count_tables(array $h)
    {
        $K = [];
        foreach ($h as $i) {
            $K[$i] = count(get_vals("SHOW TABLES IN " . idf_escape($i)));
        }
        return $K;
    }
    function table_status($D = "", $Fc = false)
    {
        $K = [];
        foreach (
            get_rows(
                $Fc
                    ? "SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() " .
                        ($D != ""
                            ? "AND TABLE_NAME = " . q($D)
                            : "ORDER BY Name")
                    : "SHOW TABLE STATUS" .
                        ($D != "" ? " LIKE " . q(addcslashes($D, "%_\\")) : ""),
            )
            as $L
        ) {
            if ($L["Engine"] == "InnoDB") {
                $L["Comment"] = preg_replace(
                    "~(?:(.+); )?InnoDB free: .*~",
                    '\1',
                    $L["Comment"],
                );
            }
            if (!isset($L["Engine"])) {
                $L["Comment"] = "";
            }
            if ($D != "") {
                $L["Name"] = $D;
            }
            $K[$L["Name"]] = $L;
        }
        return $K;
    }
    function is_view(array $S)
    {
        return $S["Engine"] === null;
    }
    function fk_support(array $S)
    {
        return preg_match(
            "~InnoDB|IBMDB2I" . (min_version(5.6) ? "|NDB" : "") . "~i",
            $S["Engine"],
        );
    }
    function fields($R)
    {
        $ne = connection()->flavor == "maria";
        $K = [];
        foreach (
            get_rows(
                "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " .
                    q($R) .
                    " ORDER BY ORDINAL_POSITION",
            )
            as $L
        ) {
            $l = $L["COLUMN_NAME"];
            $U = $L["COLUMN_TYPE"];
            $Zc = $L["GENERATION_EXPRESSION"];
            $Cc = $L["EXTRA"];
            preg_match("~^(VIRTUAL|PERSISTENT|STORED)~", $Cc, $Yc);
            preg_match(
                '~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',
                $U,
                $pe,
            );
            $j = $L["COLUMN_DEFAULT"];
            if ($j != "") {
                $Pd = preg_match("~text|json~", $pe[1]);
                if (!$ne && $Pd) {
                    $j = preg_replace(
                        "~^(_\w+)?('.*')$~",
                        '\2',
                        stripslashes($j),
                    );
                }
                if ($ne || $Pd) {
                    $j =
                        $j == "NULL"
                            ? null
                            : preg_replace_callback(
                                "~^'(.*)'$~",
                                function ($B) {
                                    return stripslashes(
                                        str_replace("''", "'", $B[1]),
                                    );
                                },
                                $j,
                            );
                }
                if (
                    !$ne &&
                    preg_match("~binary~", $pe[1]) &&
                    preg_match('~^0x(\w*)$~', $j, $B)
                ) {
                    $j = pack("H*", $B[1]);
                }
            }
            $K[$l] = [
                "field" => $l,
                "full_type" => $U,
                "type" => $pe[1],
                "length" => $pe[2],
                "unsigned" => ltrim($pe[3] . $pe[4]),
                "default" => $Yc ? ($ne ? $Zc : stripslashes($Zc)) : $j,
                "null" => $L["IS_NULLABLE"] == "YES",
                "auto_increment" => $Cc == "auto_increment",
                "on_update" => preg_match("~\bon update (\w+)~i", $Cc, $B)
                    ? $B[1]
                    : "",
                "collation" => $L["COLLATION_NAME"],
                "privileges" => array_flip(
                    explode(",", "$L[PRIVILEGES],where,order"),
                ),
                "comment" => $L["COLUMN_COMMENT"],
                "primary" => $L["COLUMN_KEY"] == "PRI",
                "generated" => $Yc[1] == "PERSISTENT" ? "STORED" : $Yc[1],
            ];
        }
        return $K;
    }
    function indexes($R, $f = null)
    {
        $K = [];
        foreach (get_rows("SHOW INDEX FROM " . table($R), $f) as $L) {
            $D = $L["Key_name"];
            $K[$D]["type"] =
                $D == "PRIMARY"
                    ? "PRIMARY"
                    : ($L["Index_type"] == "FULLTEXT"
                        ? "FULLTEXT"
                        : ($L["Non_unique"]
                            ? ($L["Index_type"] == "SPATIAL"
                                ? "SPATIAL"
                                : "INDEX")
                            : "UNIQUE"));
            $K[$D]["columns"][] = $L["Column_name"];
            $K[$D]["lengths"][] =
                $L["Index_type"] == "SPATIAL" ? null : $L["Sub_part"];
            $K[$D]["descs"][] = null;
        }
        return $K;
    }
    function foreign_keys($R)
    {
        static $If = '(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';
        $K = [];
        $sb = get_val("SHOW CREATE TABLE " . table($R), 1);
        if ($sb) {
            preg_match_all(
                "~CONSTRAINT ($If) FOREIGN KEY ?\\(((?:$If,? ?)+)\\) REFERENCES ($If)(?:\\.($If))? \\(((?:$If,? ?)+)\\)(?: ON DELETE (" .
                    driver()->onActions .
                    "))?(?: ON UPDATE (" .
                    driver()->onActions .
                    "))?~",
                $sb,
                $qe,
                PREG_SET_ORDER,
            );
            foreach ($qe as $B) {
                preg_match_all("~$If~", $B[2], $Vg);
                preg_match_all("~$If~", $B[5], $xh);
                $K[idf_unescape($B[1])] = [
                    "db" => idf_unescape($B[4] != "" ? $B[3] : $B[4]),
                    "table" => idf_unescape($B[4] != "" ? $B[4] : $B[3]),
                    "source" => array_map("Adminer\idf_unescape", $Vg[0]),
                    "target" => array_map("Adminer\idf_unescape", $xh[0]),
                    "on_delete" => $B[6] ?: "RESTRICT",
                    "on_update" => $B[7] ?: "RESTRICT",
                ];
            }
        }
        return $K;
    }
    function view($D)
    {
        return [
            "select" => preg_replace(
                "~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU",
                "",
                get_val("SHOW CREATE VIEW " . table($D), 1),
            ),
        ];
    }
    function collations()
    {
        $K = [];
        foreach (get_rows("SHOW COLLATION") as $L) {
            if ($L["Default"]) {
                $K[$L["Charset"]][-1] = $L["Collation"];
            } else {
                $K[$L["Charset"]][] = $L["Collation"];
            }
        }
        ksort($K);
        foreach ($K as $y => $X) {
            sort($K[$y]);
        }
        return $K;
    }
    function information_schema($i)
    {
        return $i == "information_schema" ||
            (min_version(5.5) && $i == "performance_schema");
    }
    function error()
    {
        return h(
            preg_replace(
                "~^You have an error.*syntax to use~U",
                "Syntax error",
                connection()->error,
            ),
        );
    }
    function create_database($i, $bb)
    {
        return queries(
            "CREATE DATABASE " .
                idf_escape($i) .
                ($bb ? " COLLATE " . q($bb) : ""),
        );
    }
    function drop_databases(array $h)
    {
        $K = apply_queries("DROP DATABASE", $h, "Adminer\idf_escape");
        restart_session();
        set_session("dbs", null);
        return $K;
    }
    function rename_database($D, $bb)
    {
        $K = false;
        if (create_database($D, $bb)) {
            $T = [];
            $ti = [];
            foreach (tables_list() as $R => $U) {
                if ($U == "VIEW") {
                    $ti[] = $R;
                } else {
                    $T[] = $R;
                }
            }
            $K = (!$T && !$ti) || move_tables($T, $ti, $D);
            drop_databases($K ? [DB] : []);
        }
        return $K;
    }
    function auto_increment()
    {
        $xa = " PRIMARY KEY";
        if ($_GET["create"] != "" && $_POST["auto_increment_col"]) {
            foreach (indexes($_GET["create"]) as $w) {
                if (
                    in_array(
                        $_POST["fields"][$_POST["auto_increment_col"]]["orig"],
                        $w["columns"],
                        true,
                    )
                ) {
                    $xa = "";
                    break;
                }
                if ($w["type"] == "PRIMARY") {
                    $xa = " UNIQUE";
                }
            }
        }
        return " AUTO_INCREMENT$xa";
    }
    function alter_table($R, $D, array $m, array $Qc, $fb, $jc, $bb, $wa, $Ef)
    {
        $oa = [];
        foreach ($m as $l) {
            if ($l[1]) {
                $j = $l[1][3];
                if (preg_match("~ GENERATED~", $j)) {
                    $l[1][3] = connection()->flavor == "maria" ? "" : $l[1][2];
                    $l[1][2] = $j;
                }
                $oa[] =
                    ($R != ""
                        ? ($l[0] != ""
                            ? "CHANGE " . idf_escape($l[0])
                            : "ADD")
                        : " ") .
                    " " .
                    implode($l[1]) .
                    ($R != "" ? $l[2] : "");
            } else {
                $oa[] = "DROP " . idf_escape($l[0]);
            }
        }
        $oa = array_merge($oa, $Qc);
        $dh =
            ($fb !== null ? " COMMENT=" . q($fb) : "") .
            ($jc ? " ENGINE=" . q($jc) : "") .
            ($bb ? " COLLATE " . q($bb) : "") .
            ($wa != "" ? " AUTO_INCREMENT=$wa" : "");
        if ($R == "") {
            return queries(
                "CREATE TABLE " .
                    table($D) .
                    " (\n" .
                    implode(",\n", $oa) .
                    "\n)$dh$Ef",
            );
        }
        if ($R != $D) {
            $oa[] = "RENAME TO " . table($D);
        }
        if ($dh) {
            $oa[] = ltrim($dh);
        }
        return $oa || $Ef
            ? queries(
                "ALTER TABLE " . table($R) . "\n" . implode(",\n", $oa) . $Ef,
            )
            : true;
    }
    function alter_indexes($R, $oa)
    {
        $Oa = [];
        foreach ($oa as $X) {
            $Oa[] =
                $X[2] == "DROP"
                    ? "\nDROP INDEX " . idf_escape($X[1])
                    : "\nADD $X[0] " .
                        ($X[0] == "PRIMARY" ? "KEY " : "") .
                        ($X[1] != "" ? idf_escape($X[1]) . " " : "") .
                        "(" .
                        implode(", ", $X[2]) .
                        ")";
        }
        return queries("ALTER TABLE " . table($R) . implode(",", $Oa));
    }
    function truncate_tables(array $T)
    {
        return apply_queries("TRUNCATE TABLE", $T);
    }
    function drop_views(array $ti)
    {
        return queries(
            "DROP VIEW " . implode(", ", array_map('Adminer\table', $ti)),
        );
    }
    function drop_tables(array $T)
    {
        return queries(
            "DROP TABLE " . implode(", ", array_map('Adminer\table', $T)),
        );
    }
    function move_tables(array $T, array $ti, $xh)
    {
        $rg = [];
        foreach ($T as $R) {
            $rg[] = table($R) . " TO " . idf_escape($xh) . "." . table($R);
        }
        if (!$rg || queries("RENAME TABLE " . implode(", ", $rg))) {
            $Jb = [];
            foreach ($ti as $R) {
                $Jb[table($R)] = view($R);
            }
            connection()->select_db($xh);
            $i = idf_escape(DB);
            foreach ($Jb as $D => $si) {
                if (
                    !queries(
                        "CREATE VIEW $D AS " .
                            str_replace(" $i.", " ", $si["select"]),
                    ) ||
                    !queries("DROP VIEW $i.$D")
                ) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    function copy_tables(array $T, array $ti, $xh)
    {
        queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
        foreach ($T as $R) {
            $D =
                $xh == DB
                    ? table("copy_$R")
                    : idf_escape($xh) . "." . table($R);
            if (
                ($_POST["overwrite"] &&
                    !queries("\nDROP TABLE IF EXISTS $D")) ||
                !queries("CREATE TABLE $D LIKE " . table($R)) ||
                !queries("INSERT INTO $D SELECT * FROM " . table($R))
            ) {
                return false;
            }
            foreach (
                get_rows("SHOW TRIGGERS LIKE " . q(addcslashes($R, "%_\\")))
                as $L
            ) {
                $Sh = $L["Trigger"];
                if (
                    !queries(
                        "CREATE TRIGGER " .
                            ($xh == DB
                                ? idf_escape("copy_$Sh")
                                : idf_escape($xh) . "." . idf_escape($Sh)) .
                            " $L[Timing] $L[Event] ON $D FOR EACH ROW\n$L[Statement];",
                    )
                ) {
                    return false;
                }
            }
        }
        foreach ($ti as $R) {
            $D =
                $xh == DB
                    ? table("copy_$R")
                    : idf_escape($xh) . "." . table($R);
            $si = view($R);
            if (
                ($_POST["overwrite"] && !queries("DROP VIEW IF EXISTS $D")) ||
                !queries("CREATE VIEW $D AS $si[select]")
            ) {
                return false;
            }
        }
        return true;
    }
    function trigger($D, $R)
    {
        if ($D == "") {
            return [];
        }
        $M = get_rows("SHOW TRIGGERS WHERE `Trigger` = " . q($D));
        return reset($M);
    }
    function triggers($R)
    {
        $K = [];
        foreach (
            get_rows("SHOW TRIGGERS LIKE " . q(addcslashes($R, "%_\\")))
            as $L
        ) {
            $K[$L["Trigger"]] = [$L["Timing"], $L["Event"]];
        }
        return $K;
    }
    function trigger_options()
    {
        return [
            "Timing" => ["BEFORE", "AFTER"],
            "Event" => ["INSERT", "UPDATE", "DELETE"],
            "Type" => ["FOR EACH ROW"],
        ];
    }
    function routine($D, $U)
    {
        $ma = [
            "bool",
            "boolean",
            "integer",
            "double precision",
            "real",
            "dec",
            "numeric",
            "fixed",
            "national char",
            "national varchar",
        ];
        $Wg = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
        $lc = driver()->enumLength;
        $Wh =
            "((" .
            implode("|", array_merge(array_keys(driver()->types()), $ma)) .
            ")\\b(?:\\s*\\(((?:[^'\")]|$lc)++)\\))?" .
            "\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";
        $If =
            "$Wg*(" .
            ($U == "FUNCTION" ? "" : driver()->inout) .
            ")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$Wh";
        $g = get_val("SHOW CREATE $U " . idf_escape($D), 2);
        preg_match(
            "~\\(((?:$If\\s*,?)*)\\)\\s*" .
                ($U == "FUNCTION" ? "RETURNS\\s+$Wh\\s+" : "") .
                "(.*)~is",
            $g,
            $B,
        );
        $m = [];
        preg_match_all("~$If\\s*,?~is", $B[1], $qe, PREG_SET_ORDER);
        foreach ($qe as $Af) {
            $m[] = [
                "field" => str_replace("``", "`", $Af[2]) . $Af[3],
                "type" => strtolower($Af[5]),
                "length" => preg_replace_callback(
                    "~$lc~s",
                    'Adminer\normalize_enum',
                    $Af[6],
                ),
                "unsigned" => strtolower(
                    preg_replace("~\s+~", " ", trim("$Af[8] $Af[7]")),
                ),
                "null" => true,
                "full_type" => $Af[4],
                "inout" => strtoupper($Af[1]),
                "collation" => strtolower($Af[9]),
            ];
        }
        return [
            "fields" => $m,
            "comment" => get_val(
                "SELECT ROUTINE_COMMENT FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = " .
                    q($D),
            ),
        ] +
            ($U != "FUNCTION"
                ? ["definition" => $B[11]]
                : [
                    "returns" => [
                        "type" => $B[12],
                        "length" => $B[13],
                        "unsigned" => $B[15],
                        "collation" => $B[16],
                    ],
                    "definition" => $B[17],
                    "language" => "SQL",
                ]);
    }
    function routines()
    {
        return get_rows(
            "SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()",
        );
    }
    function routine_languages()
    {
        return [];
    }
    function routine_id($D, array $L)
    {
        return idf_escape($D);
    }
    function last_id($J)
    {
        return get_val("SELECT LAST_INSERT_ID()");
    }
    function explain(Db $e, $I)
    {
        return $e->query(
            "EXPLAIN " .
                (min_version(5.1) && !min_version(5.7) ? "PARTITIONS " : "") .
                $I,
        );
    }
    function found_rows(array $S, array $Z)
    {
        return $Z || $S["Engine"] != "InnoDB" ? null : $S["Rows"];
    }
    function create_sql($R, $wa, $ih)
    {
        $K = get_val("SHOW CREATE TABLE " . table($R), 1);
        if (!$wa) {
            $K = preg_replace("~ AUTO_INCREMENT=\d+~", "", $K);
        }
        return $K;
    }
    function truncate_sql($R)
    {
        return "TRUNCATE " . table($R);
    }
    function use_sql($Bb)
    {
        return "USE " . idf_escape($Bb);
    }
    function trigger_sql($R)
    {
        $K = "";
        foreach (
            get_rows(
                "SHOW TRIGGERS LIKE " . q(addcslashes($R, "%_\\")),
                null,
                "-- ",
            )
            as $L
        ) {
            $K .=
                "\nCREATE TRIGGER " .
                idf_escape($L["Trigger"]) .
                " $L[Timing] $L[Event] ON " .
                table($L["Table"]) .
                " FOR EACH ROW\n$L[Statement];;\n";
        }
        return $K;
    }
    function show_variables()
    {
        return get_rows("SHOW VARIABLES");
    }
    function show_status()
    {
        return get_rows("SHOW STATUS");
    }
    function process_list()
    {
        return get_rows("SHOW FULL PROCESSLIST");
    }
    function convert_field(array $l)
    {
        if (preg_match("~binary~", $l["type"])) {
            return "HEX(" . idf_escape($l["field"]) . ")";
        }
        if ($l["type"] == "bit") {
            return "BIN(" . idf_escape($l["field"]) . " + 0)";
        }
        if (preg_match("~geometry|point|linestring|polygon~", $l["type"])) {
            return (min_version(8) ? "ST_" : "") .
                "AsWKT(" .
                idf_escape($l["field"]) .
                ")";
        }
    }
    function unconvert_field(array $l, $K)
    {
        if (preg_match("~binary~", $l["type"])) {
            $K = "UNHEX($K)";
        }
        if ($l["type"] == "bit") {
            $K = "CONVERT(b$K, UNSIGNED)";
        }
        if (preg_match("~geometry|point|linestring|polygon~", $l["type"])) {
            $Uf = min_version(8) ? "ST_" : "";
            $K = $Uf . "GeomFromText($K, $Uf" . "SRID($l[field]))";
        }
        return $K;
    }
    function support($Gc)
    {
        return !preg_match(
            "~scheme|sequence|type|view_trigger|materializedview" .
                (min_version(8)
                    ? ""
                    : "|descidx" .
                        (min_version(5.1) ? "" : "|event|partitioning")) .
                (min_version("8.0.16", "10.2.1") ? "" : "|check") .
                "~",
            $Gc,
        );
    }
    function kill_process($X)
    {
        return queries("KILL " . number($X));
    }
    function connection_id()
    {
        return "SELECT CONNECTION_ID()";
    }
    function max_connections()
    {
        return get_val("SELECT @@max_connections");
    }
    function types()
    {
        return [];
    }
    function type_values($u)
    {
        return "";
    }
    function schemas()
    {
        return [];
    }
    function get_schema()
    {
        return "";
    }
    function set_schema($Bg, $f = null)
    {
        return true;
    }
}
define("Adminer\JUSH", Driver::$Sd);
define("Adminer\SERVER", $_GET[DRIVER]);
define("Adminer\DB", $_GET["db"]);
define(
    "Adminer\ME",
    preg_replace("~\?.*~", "", relative_uri()) .
        "?" .
        (sid() ? SID . "&" : "") .
        (SERVER !== null ? DRIVER . "=" . urlencode(SERVER) . "&" : "") .
        ($_GET["ext"] ? "ext=" . urlencode($_GET["ext"]) . "&" : "") .
        (isset($_GET["username"])
            ? "username=" . urlencode($_GET["username"]) . "&"
            : "") .
        (DB != ""
            ? "db=" .
                urlencode(DB) .
                "&" .
                (isset($_GET["ns"]) ? "ns=" . urlencode($_GET["ns"]) . "&" : "")
            : ""),
);
function page_header($Fh, $k = "", $Ia = [], $Gh = "")
{
    page_headers();
    if (is_ajax() && $k) {
        page_messages($k);
        exit();
    }
    if (!ob_get_level()) {
        ob_start("ob_gzhandler", 4096);
    }
    $Hh = $Fh . ($Gh != "" ? ": $Gh" : "");
    $Ih = strip_tags(
        $Hh .
            (SERVER != "" && SERVER != "localhost" ? h(" - " . SERVER) : "") .
            " - " .
            adminer()->name(),
    );
    echo '<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',
        $Ih,
        '</title>
<link rel="stylesheet" href="',
        h(preg_replace("~\\?.*~", "", ME) . "?file=default.css&version=5.2.1"),
        '">
';
    $wb = adminer()->css();
    $kd = false;
    $id = false;
    foreach ($wb as $n) {
        if (strpos($n, "adminer.css") !== false) {
            $kd = true;
        }
        if (strpos($n, "adminer-dark.css") !== false) {
            $id = true;
        }
    }
    $zb = $kd ? ($id ? null : false) : ($id ?: null);
    $ye = " media='(prefers-color-scheme: dark)'";
    if ($zb !== false) {
        echo "<link rel='stylesheet'" .
            ($zb ? "" : $ye) .
            " href='" .
            h(
                preg_replace("~\\?.*~", "", ME) .
                    "?file=dark.css&version=5.2.1",
            ) .
            "'>\n";
    }
    echo "<meta name='color-scheme' content='" .
    ($zb === null ? "light dark" : ($zb ? "dark" : "light")) .
    "'>\n",
        script_src(
            preg_replace("~\\?.*~", "", ME) .
                "?file=functions.js&version=5.2.1",
        );
    if (adminer()->head($zb)) {
        echo "<link rel='icon' href='data:image/gif;base64,R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n",
            "<link rel='apple-touch-icon' href='" .
                h(
                    preg_replace("~\\?.*~", "", ME) .
                        "?file=logo.png&version=5.2.1",
                ) .
                "'>\n";
    }
    foreach ($wb as $X) {
        echo "<link rel='stylesheet'" .
            (preg_match("~-dark\.~", $X) && !$zb ? $ye : "") .
            " href='" .
            h($X) .
            "'>\n";
    }
    echo "\n<body class='" . "ltr" . " nojs'>\n";
    $n = get_temp_dir() . "/adminer.version";
    if (
        !$_COOKIE["adminer_version"] &&
        function_exists("openssl_verify") &&
        file_exists($n) &&
        filemtime($n) + 86400 > time()
    ) {
        $ri = unserialize(file_get_contents($n));
        $dg = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwqWOVuF5uw7/+Z70djoK
RlHIZFZPO0uYRezq90+7Amk+FDNd7KkL5eDve+vHRJBLAszF/7XKXe11xwliIsFs
DFWQlsABVZB3oisKCBEuI71J4kPH8dKGEWR9jDHFw3cWmoH3PmqImX6FISWbG3B8
h7FIx3jEaw5ckVPVTeo5JRm/1DZzJxjyDenXvBQ/6o9DgZKeNDgxwKzH+sw9/YCO
jHnq1cFpOIISzARlrHMa/43YfeNRAm/tsBXjSxembBPo7aQZLAWHmaj5+K19H10B
nCpz9Y++cipkVEiKRGih4ZEvjoFysEOdRLj6WiD/uUNky4xGeA6LaJqh5XpkFkcQ
fQIDAQAB
-----END PUBLIC KEY-----
";
        if (
            openssl_verify(
                $ri["version"],
                base64_decode($ri["signature"]),
                $dg,
            ) == 1
        ) {
            $_COOKIE["adminer_version"] = $ri["version"];
        }
    }
    echo script(
    "mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick" .
        (isset($_COOKIE["adminer_version"])
            ? ""
            : ", onload: partial(verifyVersion, '" .
                VERSION .
                "', '" .
                js_escape(ME) .
                "', '" .
                get_token() .
                "')") .
        "});
document.body.classList.replace('nojs', 'js');
const offlineMessage = '" .
        js_escape("You are offline.") .
        "';
const thousandsSeparator = '" .
        js_escape(",") .
        "';",
),
        "<div id='help' class='jush-" . JUSH . " jsonly hidden'></div>\n",
        script(
            "mixin(qs('#help'), {onmouseover: () => { helpOpen = 1; }, onmouseout: helpMouseout});",
        ),
        "<div id='content'>\n",
        "<span id='menuopen' class='jsonly'>" .
            icon("move", "", "menu", "") .
            "</span>" .
            script(
                "qs('#menuopen').onclick = event => { qs('#foot').classList.toggle('foot'); event.stopPropagation(); }",
            );
    if ($Ia !== null) {
        $_ = substr(preg_replace("~\b(username|db|ns)=[^&]*&~", "", ME), 0, -1);
        echo '<p id="breadcrumb"><a href="' .
            h($_ ?: ".") .
            '">' .
            get_driver(DRIVER) .
            "</a> » ";
        $_ = substr(preg_replace("~\b(db|ns)=[^&]*&~", "", ME), 0, -1);
        $P = adminer()->serverName(SERVER);
        $P = $P != "" ? $P : "Server";
        if ($Ia === false) {
            echo "$P\n";
        } else {
            echo "<a href='" .
                h($_) .
                "' accesskey='1' title='Alt+Shift+1'>$P</a> » ";
            if ($_GET["ns"] != "" || (DB != "" && is_array($Ia))) {
                echo '<a href="' .
                    h(
                        $_ .
                            "&db=" .
                            urlencode(DB) .
                            (support("scheme") ? "&ns=" : ""),
                    ) .
                    '">' .
                    h(DB) .
                    "</a> » ";
            }
            if (is_array($Ia)) {
                if ($_GET["ns"] != "") {
                    echo '<a href="' .
                        h(substr(ME, 0, -1)) .
                        '">' .
                        h($_GET["ns"]) .
                        "</a> » ";
                }
                foreach ($Ia as $y => $X) {
                    $Lb = is_array($X) ? $X[1] : h($X);
                    if ($Lb != "") {
                        echo "<a href='" .
                            h(ME . "$y=") .
                            urlencode(is_array($X) ? $X[0] : $X) .
                            "'>$Lb</a> » ";
                    }
                }
            }
            echo "$Fh\n";
        }
    }
    echo "<h2>$Hh</h2>\n",
        "<div id='ajaxstatus' class='jsonly hidden'></div>\n";
    restart_session();
    page_messages($k);
    $h = &get_session("dbs");
    if (DB != "" && $h && !in_array(DB, $h, true)) {
        $h = null;
    }
    stop_session();
    define("Adminer\PAGE_HEADER", 1);
}
function page_headers()
{
    header("Content-Type: text/html; charset=utf-8");
    header("Cache-Control: no-cache");
    header("X-Frame-Options: deny");
    header("X-XSS-Protection: 0");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: origin-when-cross-origin");
    foreach (adminer()->csp(csp()) as $vb) {
        $ld = [];
        foreach ($vb as $y => $X) {
            $ld[] = "$y $X";
        }
        header("Content-Security-Policy: " . implode("; ", $ld));
    }
    adminer()->headers();
}
function csp()
{
    return [
        [
            "script-src" =>
                "'self' 'unsafe-inline' 'nonce-" .
                get_nonce() .
                "' 'strict-dynamic'",
            "connect-src" => "'self'",
            "frame-src" => "https://www.adminer.org",
            "object-src" => "'none'",
            "base-uri" => "'none'",
            "form-action" => "'self'",
        ],
    ];
}
function get_nonce()
{
    static $Se;
    if (!$Se) {
        $Se = base64_encode(rand_string());
    }
    return $Se;
}
function page_messages($k)
{
    $gi = preg_replace("~^[^?]*~", "", $_SERVER["REQUEST_URI"]);
    $Ce = idx($_SESSION["messages"], $gi);
    if ($Ce) {
        echo "<div class='message'>" .
            implode("</div>\n<div class='message'>", $Ce) .
            "</div>" .
            script("messagesPrint();");
        unset($_SESSION["messages"][$gi]);
    }
    if ($k) {
        echo "<div class='error'>$k</div>\n";
    }
    if (adminer()->error) {
        echo "<div class='error'>" . adminer()->error . "</div>\n";
    }
}
function page_footer($Fe = "")
{
    echo "</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";
    adminer()->navigation($Fe);
    echo "</div>\n";
    if ($Fe != "auth") {
        echo '<form action="" method="post">
<p class="logout">
<span>',
            h($_GET["username"]) . "\n",
            '</span>
<input type="submit" name="logout" value="Logout" id="logout">
',
            input_token(),
            '</form>
';
    }
    echo "</div>\n\n", script("setupSubmitHighlight(document);");
}
function int32($Ke)
{
    while ($Ke >= 2147483648) {
        $Ke -= 4294967296;
    }
    while ($Ke <= -2147483649) {
        $Ke += 4294967296;
    }
    return (int) $Ke;
}
function long2str(array $W, $vi)
{
    $N = "";
    foreach ($W as $X) {
        $N .= pack("V", $X);
    }
    if ($vi) {
        return substr($N, 0, end($W));
    }
    return $N;
}
function str2long($N, $vi)
{
    $W = array_values(
        unpack("V*", str_pad($N, 4 * ceil(strlen($N) / 4), "\0")),
    );
    if ($vi) {
        $W[] = strlen($N);
    }
    return $W;
}
function xxtea_mx($Bi, $Ai, $lh, $Td)
{
    return int32(
        ((($Bi >> 5) & 0x7ffffff) ^ ($Ai << 2)) +
            ((($Ai >> 3) & 0x1fffffff) ^ ($Bi << 4)),
    ) ^ int32(($lh ^ $Ai) + ($Td ^ $Bi));
}
function encrypt_string($fh, $y)
{
    if ($fh == "") {
        return "";
    }
    $y = array_values(unpack("V*", pack("H*", md5($y))));
    $W = str2long($fh, true);
    $Ke = count($W) - 1;
    $Bi = $W[$Ke];
    $Ai = $W[0];
    $H = floor(6 + 52 / ($Ke + 1));
    $lh = 0;
    while ($H-- > 0) {
        $lh = int32($lh + 0x9e3779b9);
        $ac = ($lh >> 2) & 3;
        for ($zf = 0; $zf < $Ke; $zf++) {
            $Ai = $W[$zf + 1];
            $Je = xxtea_mx($Bi, $Ai, $lh, $y[($zf & 3) ^ $ac]);
            $Bi = int32($W[$zf] + $Je);
            $W[$zf] = $Bi;
        }
        $Ai = $W[0];
        $Je = xxtea_mx($Bi, $Ai, $lh, $y[($zf & 3) ^ $ac]);
        $Bi = int32($W[$Ke] + $Je);
        $W[$Ke] = $Bi;
    }
    return long2str($W, false);
}
function decrypt_string($fh, $y)
{
    if ($fh == "") {
        return "";
    }
    if (!$y) {
        return false;
    }
    $y = array_values(unpack("V*", pack("H*", md5($y))));
    $W = str2long($fh, false);
    $Ke = count($W) - 1;
    $Bi = $W[$Ke];
    $Ai = $W[0];
    $H = floor(6 + 52 / ($Ke + 1));
    $lh = int32($H * 0x9e3779b9);
    while ($lh) {
        $ac = ($lh >> 2) & 3;
        for ($zf = $Ke; $zf > 0; $zf--) {
            $Bi = $W[$zf - 1];
            $Je = xxtea_mx($Bi, $Ai, $lh, $y[($zf & 3) ^ $ac]);
            $Ai = int32($W[$zf] - $Je);
            $W[$zf] = $Ai;
        }
        $Bi = $W[$Ke];
        $Je = xxtea_mx($Bi, $Ai, $lh, $y[($zf & 3) ^ $ac]);
        $Ai = int32($W[0] - $Je);
        $W[0] = $Ai;
        $lh = int32($lh - 0x9e3779b9);
    }
    return long2str($W, true);
}
$Kf = [];
if ($_COOKIE["adminer_permanent"]) {
    foreach (explode(" ", $_COOKIE["adminer_permanent"]) as $X) {
        [$y] = explode(":", $X);
        $Kf[$y] = $X;
    }
}
function add_invalid_login()
{
    $Ba = get_temp_dir() . "/adminer.invalid";
    foreach (glob("$Ba*") ?: [$Ba] as $n) {
        $q = file_open_lock($n);
        if ($q) {
            break;
        }
    }
    if (!$q) {
        $q = file_open_lock("$Ba-" . rand_string());
    }
    if (!$q) {
        return;
    }
    $Ld = unserialize(stream_get_contents($q));
    $Ch = time();
    if ($Ld) {
        foreach ($Ld as $Md => $X) {
            if ($X[0] < $Ch) {
                unset($Ld[$Md]);
            }
        }
    }
    $Kd = &$Ld[adminer()->bruteForceKey()];
    if (!$Kd) {
        $Kd = [$Ch + 30 * 60, 0];
    }
    $Kd[1]++;
    file_write_unlock($q, serialize($Ld));
}
function check_invalid_login(array &$Kf)
{
    $Ld = [];
    foreach (glob(get_temp_dir() . "/adminer.invalid*") as $n) {
        $q = file_open_lock($n);
        if ($q) {
            $Ld = unserialize(stream_get_contents($q));
            file_unlock($q);
            break;
        }
    }
    $Kd = idx($Ld, adminer()->bruteForceKey(), []);
    $Re = $Kd[1] > 29 ? $Kd[0] - time() : 0;
    if ($Re > 0) {
        auth_error(
            lang_format(
                [
                    "Too many unsuccessful logins, try again in %d minute.",
                    "Too many unsuccessful logins, try again in %d minutes.",
                ],
                ceil($Re / 60),
            ),
            $Kf,
        );
    }
}
$va = $_POST["auth"];
if ($va) {
    session_regenerate_id();
    $qi = $va["driver"];
    $P = $va["server"];
    $V = $va["username"];
    $G = (string) $va["password"];
    $i = $va["db"];
    set_password($qi, $P, $V, $G);
    $_SESSION["db"][$qi][$P][$V][$i] = true;
    if ($va["permanent"]) {
        $y = implode("-", array_map("base64_encode", [$qi, $P, $V, $i]));
        $Yf = adminer()->permanentLogin(true);
        $Kf[$y] = "$y:" . base64_encode($Yf ? encrypt_string($G, $Yf) : "");
        cookie("adminer_permanent", implode(" ", $Kf));
    }
    if (
        count($_POST) == 1 ||
        DRIVER != $qi ||
        SERVER != $P ||
        $_GET["username"] !== $V ||
        DB != $i
    ) {
        redirect(auth_url($qi, $P, $V, $i));
    }
} elseif ($_POST["logout"] && (!$_SESSION["token"] || verify_token())) {
    foreach (["pwds", "db", "dbs", "queries"] as $y) {
        set_session($y, null);
    }
    unset_permanent($Kf);
    redirect(
        substr(preg_replace("~\b(username|db|ns)=[^&]*&~", "", ME), 0, -1),
        "Logout successful." .
            " " .
            'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.',
    );
} elseif ($Kf && !$_SESSION["pwds"]) {
    session_regenerate_id();
    $Yf = adminer()->permanentLogin();
    foreach ($Kf as $y => $X) {
        [, $Va] = explode(":", $X);
        [$qi, $P, $V, $i] = array_map("base64_decode", explode("-", $y));
        set_password($qi, $P, $V, decrypt_string(base64_decode($Va), $Yf));
        $_SESSION["db"][$qi][$P][$V][$i] = true;
    }
}
function unset_permanent(array &$Kf)
{
    foreach ($Kf as $y => $X) {
        [$qi, $P, $V, $i] = array_map("base64_decode", explode("-", $y));
        if (
            $qi == DRIVER &&
            $P == SERVER &&
            $V == $_GET["username"] &&
            $i == DB
        ) {
            unset($Kf[$y]);
        }
    }
    cookie("adminer_permanent", implode(" ", $Kf));
}
function auth_error($k, array &$Kf)
{
    $Ng = session_name();
    if (isset($_GET["username"])) {
        header("HTTP/1.1 403 Forbidden");
        if (($_COOKIE[$Ng] || $_GET[$Ng]) && !$_SESSION["token"]) {
            $k = "Session expired, please login again.";
        } else {
            restart_session();
            add_invalid_login();
            $G = get_password();
            if ($G !== null) {
                if ($G === false) {
                    $k .=
                        ($k ? "<br>" : "") .
                        sprintf(
                            'Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',
                            target_blank(),
                            "<code>permanentLogin()</code>",
                        );
                }
                set_password(DRIVER, SERVER, $_GET["username"], null);
            }
            unset_permanent($Kf);
        }
    }
    if (!$_COOKIE[$Ng] && $_GET[$Ng] && ini_bool("session.use_only_cookies")) {
        $k = "Session support must be enabled.";
    }
    $F = session_get_cookie_params();
    cookie(
        "adminer_key",
        $_COOKIE["adminer_key"] ?: rand_string(),
        $F["lifetime"],
    );
    if (!$_SESSION["token"]) {
        $_SESSION["token"] = rand(1, 1e6);
    }
    page_header("Login", $k, null);
    echo "<form action='' method='post'>\n", "<div>";
    if (hidden_fields($_POST, ["auth"])) {
        echo "<p class='message'>" .
            "The action will be performed after successful login with the same credentials." .
            "\n";
    }
    echo "</div>\n";
    adminer()->loginForm();
    echo "</form>\n";
    page_footer("auth");
    exit();
}
if (isset($_GET["username"]) && !class_exists("Adminer\Db")) {
    unset($_SESSION["pwds"][DRIVER]);
    unset_permanent($Kf);
    page_header(
        "No extension",
        sprintf(
            "None of the supported PHP extensions (%s) are available.",
            implode(", ", Driver::$Bc),
        ),
        false,
    );
    page_footer("auth");
    exit();
}
$e = "";
if (isset($_GET["username"]) && is_string(get_password())) {
    [$rd, $Of] = explode(":", SERVER, 2);
    if (
        preg_match("~^\s*([-+]?\d+)~", $Of, $B) &&
        ($B[1] < 1024 || $B[1] > 65535)
    ) {
        auth_error("Connecting to privileged ports is not allowed.", $Kf);
    }
    check_invalid_login($Kf);
    $ub = adminer()->credentials();
    $e = Driver::connect($ub[0], $ub[1], $ub[2]);
    if (is_object($e)) {
        Db::$Id = $e;
        Driver::$Id = new Driver($e);
        if ($e->flavor) {
            save_settings([
                "vendor-" . DRIVER . "-" . SERVER => get_driver(DRIVER),
            ]);
        }
    }
}
$le = null;
if (
    !is_object($e) ||
    ($le = adminer()->login($_GET["username"], get_password())) !== true
) {
    $k =
        (is_string($e)
            ? nl_br(h($e))
            : (is_string($le)
                ? $le
                : "Invalid credentials.")) .
        (preg_match('~^ | $~', get_password())
            ? "<br>" .
                "There is a space in the input password which might be the cause."
            : "");
    auth_error($k, $Kf);
}
if ($_POST["logout"] && $_SESSION["token"] && !verify_token()) {
    page_header("Logout", "Invalid CSRF token. Send the form again.");
    page_footer("db");
    exit();
}
if (!$_SESSION["token"]) {
    $_SESSION["token"] = rand(1, 1e6);
}
stop_session(true);
if ($va && $_POST["token"]) {
    $_POST["token"] = get_token();
}
$k = "";
if ($_POST) {
    if (!verify_token()) {
        $Dd = "max_input_vars";
        $we = ini_get($Dd);
        if (extension_loaded("suhosin")) {
            foreach (
                ["suhosin.request.max_vars", "suhosin.post.max_vars"]
                as $y
            ) {
                $X = ini_get($y);
                if ($X && (!$we || $X < $we)) {
                    $Dd = $y;
                    $we = $X;
                }
            }
        }
        $k =
            !$_POST["token"] && $we
                ? sprintf(
                    "Maximum number of allowed fields exceeded. Please increase %s.",
                    "'$Dd'",
                )
                : "Invalid CSRF token. Send the form again." .
                    " " .
                    "If you did not send this request from Adminer then close this page.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $k = sprintf(
        "Too big POST data. Reduce the data or increase the %s configuration directive.",
        "'post_max_size'",
    );
    if (isset($_GET["sql"])) {
        $k .=
            " " .
            "You can upload a big SQL file via FTP and import it from server.";
    }
}
function print_select_result($J, $f = null, array $qf = [], $z = 0)
{
    $ke = [];
    $x = [];
    $d = [];
    $Ga = [];
    $Xh = [];
    $K = [];
    for ($t = 0; (!$z || $t < $z) && ($L = $J->fetch_row()); $t++) {
        if (!$t) {
            echo "<div class='scrollable'>\n",
                "<table class='nowrap odds'>\n",
                "<thead><tr>";
            for ($Rd = 0; $Rd < count($L); $Rd++) {
                $l = $J->fetch_field();
                $D = $l->name;
                $pf = isset($l->orgtable) ? $l->orgtable : "";
                $of = isset($l->orgname) ? $l->orgname : $D;
                if ($qf && JUSH == "sql") {
                    $ke[$Rd] =
                        $D == "table"
                            ? "table="
                            : ($D == "possible_keys"
                                ? "indexes="
                                : null);
                } elseif ($pf != "") {
                    if (isset($l->table)) {
                        $K[$l->table] = $pf;
                    }
                    if (!isset($x[$pf])) {
                        $x[$pf] = [];
                        foreach (indexes($pf, $f) as $w) {
                            if ($w["type"] == "PRIMARY") {
                                $x[$pf] = array_flip($w["columns"]);
                                break;
                            }
                        }
                        $d[$pf] = $x[$pf];
                    }
                    if (isset($d[$pf][$of])) {
                        unset($d[$pf][$of]);
                        $x[$pf][$of] = $Rd;
                        $ke[$Rd] = $pf;
                    }
                }
                if ($l->charsetnr == 63) {
                    $Ga[$Rd] = true;
                }
                $Xh[$Rd] = $l->type;
                echo "<th" .
                    ($pf != "" || $l->name != $of
                        ? " title='" . h(($pf != "" ? "$pf." : "") . $of) . "'"
                        : "") .
                    ">" .
                    h($D) .
                    ($qf
                        ? doc_link([
                            "sql" =>
                                "explain-output.html#explain_" . strtolower($D),
                            "mariadb" =>
                                "explain/#the-columns-in-explain-select",
                        ])
                        : "");
            }
            echo "</thead>\n";
        }
        echo "<tr>";
        foreach ($L as $y => $X) {
            $_ = "";
            if (isset($ke[$y]) && !$d[$ke[$y]]) {
                if ($qf && JUSH == "sql") {
                    $R = $L[array_search("table=", $ke)];
                    $_ = ME . $ke[$y] . urlencode($qf[$R] != "" ? $qf[$R] : $R);
                } else {
                    $_ = ME . "edit=" . urlencode($ke[$y]);
                    foreach ($x[$ke[$y]] as $Za => $Rd) {
                        $_ .=
                            "&where" .
                            urlencode("[" . bracket_escape($Za) . "]") .
                            "=" .
                            urlencode($L[$Rd]);
                    }
                }
            } elseif (is_url($X)) {
                $_ = $X;
            }
            if ($X === null) {
                $X = "<i>NULL</i>";
            } elseif ($Ga[$y] && !is_utf8($X)) {
                $X =
                    "<i>" .
                    lang_format(["%d byte", "%d bytes"], strlen($X)) .
                    "</i>";
            } else {
                $X = h($X);
                if ($Xh[$y] == 254) {
                    $X = "<code>$X</code>";
                }
            }
            if ($_) {
                $X =
                    "<a href='" .
                    h($_) .
                    "'" .
                    (is_url($_) ? target_blank() : "") .
                    ">$X</a>";
            }
            echo "<td" .
                ($Xh[$y] <= 9 || $Xh[$y] == 246 ? " class='number'" : "") .
                ">$X";
        }
    }
    echo ($t ? "</table>\n</div>" : "<p class='message'>" . "No rows.") . "\n";
    return $K;
}
function referencable_primary($Ig)
{
    $K = [];
    foreach (table_status("", true) as $ph => $R) {
        if ($ph != $Ig && fk_support($R)) {
            foreach (fields($ph) as $l) {
                if ($l["primary"]) {
                    if ($K[$ph]) {
                        unset($K[$ph]);
                        break;
                    }
                    $K[$ph] = $l;
                }
            }
        }
    }
    return $K;
}
function textarea($D, $Y, $M = 10, $cb = 80)
{
    echo "<textarea name='" .
        h($D) .
        "' rows='$M' cols='$cb' class='sqlarea jush-" .
        JUSH .
        "' spellcheck='false' wrap='off'>";
    if (is_array($Y)) {
        foreach ($Y as $X) {
            echo h($X[0]) . "\n\n\n";
        }
    } else {
        echo h($Y);
    }
    echo "</textarea>";
}
function select_input($ua, array $kf, $Y = "", $ff = "", $Lf = "")
{
    $wh = $kf ? "select" : "input";
    return "<$wh$ua" .
        ($kf
            ? "><option value=''>$Lf" . optionlist($kf, $Y, true) . "</select>"
            : " size='10' value='" . h($Y) . "' placeholder='$Lf'>") .
        ($ff ? script("qsl('$wh').onchange = $ff;", "") : "");
}
function json_row($y, $X = null)
{
    static $Lc = true;
    if ($Lc) {
        echo "{";
    }
    if ($y != "") {
        echo ($Lc ? "" : ",") .
            "\n\t\"" .
            addcslashes($y, "\r\n\t\"\\/") .
            '": ' .
            ($X !== null ? '"' . addcslashes($X, "\r\n\"\\/") . '"' : "null");
        $Lc = false;
    } else {
        echo "\n}\n";
        $Lc = true;
    }
}
function edit_type($y, array $l, array $b, array $p = [], array $Dc = [])
{
    $U = $l["type"];
    echo "<td><select name='" .
        h($y) .
        "[type]' class='type' aria-labelledby='label-type'>";
    if (
        $U &&
        !array_key_exists($U, driver()->types()) &&
        !isset($p[$U]) &&
        !in_array($U, $Dc)
    ) {
        $Dc[] = $U;
    }
    $hh = driver()->structuredTypes();
    if ($p) {
        $hh["Foreign keys"] = $p;
    }
    echo optionlist(array_merge($Dc, $hh), $U),
        "</select><td>",
        "<input name='" .
            h($y) .
            "[length]' value='" .
            h($l["length"]) .
            "' size='3'" .
            (!$l["length"] && preg_match('~var(char|binary)$~', $U)
                ? " class='required'"
                : "") .
            " aria-labelledby='label-length'>",
        "<td class='options'>",
        $b
            ? "<input list='collations' name='" .
                h($y) .
                "[collation]'" .
                (preg_match('~(char|text|enum|set)$~', $U)
                    ? ""
                    : " class='hidden'") .
                " value='" .
                h($l["collation"]) .
                "' placeholder='(" .
                "collation" .
                ")'>"
            : "",
        driver()->unsigned
            ? "<select name='" .
                h($y) .
                "[unsigned]'" .
                (!$U || preg_match(number_type(), $U)
                    ? ""
                    : " class='hidden'") .
                "><option>" .
                optionlist(driver()->unsigned, $l["unsigned"]) .
                "</select>"
            : "",
        isset($l["on_update"])
            ? "<select name='" .
                h($y) .
                "[on_update]'" .
                (preg_match("~timestamp|datetime~", $U)
                    ? ""
                    : " class='hidden'") .
                ">" .
                optionlist(
                    ["" => "(" . "ON UPDATE" . ")", "CURRENT_TIMESTAMP"],
                    preg_match("~^CURRENT_TIMESTAMP~i", $l["on_update"])
                        ? "CURRENT_TIMESTAMP"
                        : $l["on_update"],
                ) .
                "</select>"
            : "",
        $p
            ? "<select name='" .
                h($y) .
                "[on_delete]'" .
                (preg_match("~`~", $U) ? "" : " class='hidden'") .
                "><option value=''>(" .
                "ON DELETE" .
                ")" .
                optionlist(explode("|", driver()->onActions), $l["on_delete"]) .
                "</select> "
            : " ";
}
function get_partitions_info($R)
{
    $Vc =
        "FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = " .
        q(DB) .
        " AND TABLE_NAME = " .
        q($R);
    $J = connection()->query(
        "SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $Vc ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1",
    );
    $K = [];
    [$K["partition_by"], $K["partition"], $K["partitions"]] = $J->fetch_row();
    $Ff = get_key_vals(
        "SELECT PARTITION_NAME, PARTITION_DESCRIPTION $Vc AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION",
    );
    $K["partition_names"] = array_keys($Ff);
    $K["partition_values"] = array_values($Ff);
    return $K;
}
function process_length($ge)
{
    $nc = driver()->enumLength;
    return preg_match(
        "~^\\s*\\(?\\s*$nc(?:\\s*,\\s*$nc)*+\\s*\\)?\\s*\$~",
        $ge,
    ) && preg_match_all("~$nc~", $ge, $qe)
        ? "(" . implode(",", $qe[0]) . ")"
        : preg_replace(
            "~^[0-9].*~",
            '(\0)',
            preg_replace("~[^-0-9,+()[\]]~", "", $ge),
        );
}
function process_type(array $l, $ab = "COLLATE")
{
    return " $l[type]" .
        process_length($l["length"]) .
        (preg_match(number_type(), $l["type"]) &&
        in_array($l["unsigned"], driver()->unsigned)
            ? " $l[unsigned]"
            : "") .
        (preg_match("~char|text|enum|set~", $l["type"]) && $l["collation"]
            ? " $ab " . (JUSH == "mssql" ? $l["collation"] : q($l["collation"]))
            : "");
}
function process_field(array $l, array $Vh)
{
    if ($l["on_update"]) {
        $l["on_update"] = str_ireplace(
            "current_timestamp()",
            "CURRENT_TIMESTAMP",
            $l["on_update"],
        );
    }
    return [
        idf_escape(trim($l["field"])),
        process_type($Vh),
        $l["null"] ? " NULL" : " NOT NULL",
        default_value($l),
        preg_match("~timestamp|datetime~", $l["type"]) && $l["on_update"]
            ? " ON UPDATE $l[on_update]"
            : "",
        support("comment") && $l["comment"] != ""
            ? " COMMENT " . q($l["comment"])
            : "",
        $l["auto_increment"] ? auto_increment() : null,
    ];
}
function default_value(array $l)
{
    $j = $l["default"];
    $Yc = $l["generated"];
    return $j === null
        ? ""
        : (in_array($Yc, driver()->generated)
            ? (JUSH == "mssql"
                ? " AS ($j)" . ($Yc == "VIRTUAL" ? "" : " $Yc") . ""
                : " GENERATED ALWAYS AS ($j) $Yc")
            : " DEFAULT " .
                (!preg_match("~^GENERATED ~i", $j) &&
                (preg_match("~char|binary|text|json|enum|set~", $l["type"]) ||
                    preg_match("~^(?![a-z])~i", $j))
                    ? (JUSH == "sql" && preg_match("~text|json~", $l["type"])
                        ? "(" . q($j) . ")"
                        : q($j))
                    : str_ireplace(
                        "current_timestamp()",
                        "CURRENT_TIMESTAMP",
                        JUSH == "sqlite" ? "($j)" : $j,
                    )));
}
function type_class($U)
{
    foreach (
        [
            "char" => "text",
            "date" => "time|year",
            "binary" => "blob",
            "enum" => "set",
        ]
        as $y => $X
    ) {
        if (preg_match("~$y|$X~", $U)) {
            return " class='$y'";
        }
    }
}
function edit_fields(array $m, array $b, $U = "TABLE", array $p = [])
{
    $m = array_values($m);
    $Gb = ($_POST
            ? $_POST["defaults"]
            : get_setting("defaults"))
        ? ""
        : " class='hidden'";
    $gb = ($_POST
            ? $_POST["comments"]
            : get_setting("comments"))
        ? ""
        : " class='hidden'";
    echo "<thead><tr>\n",
        $U == "PROCEDURE" ? "<td>" : "",
        "<th id='label-name'>" .
            ($U == "TABLE" ? "Column name" : "Parameter name"),
        "<td id='label-type'>" .
            "Type" .
            "<textarea id='enum-edit' rows='4' cols='12' wrap='off' style='display: none;'></textarea>" .
            script("qs('#enum-edit').onblur = editingLengthBlur;"),
        "<td id='label-length'>" . "Length",
        "<td>" . "Options";
    if ($U == "TABLE") {
        echo "<td id='label-null'>NULL\n",
            "<td><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='" .
                "Auto Increment" .
                "'>AI</abbr>",
            doc_link([
                "sql" => "example-auto-increment.html",
                "mariadb" => "auto_increment/",
            ]),
            "<td id='label-default'$Gb>" . "Default value",
            support("comment") ? "<td id='label-comment'$gb>" . "Comment" : "";
    }
    echo "<td>" .
    icon(
        "plus",
        "add[" . (support("move_col") ? 0 : count($m)) . "]",
        "+",
        "Add next",
    ),
        "</thead>\n<tbody>\n",
        script(
            "mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});",
        );
    foreach ($m as $t => $l) {
        $t++;
        $rf = $l[$_POST ? "orig" : "field"];
        $Rb =
            (isset($_POST["add"][$t - 1]) ||
                (isset($l["field"]) && !idx($_POST["drop_col"], $t))) &&
            (support("drop_col") || $rf == "");
        echo "<tr" . ($Rb ? "" : " style='display: none;'") . ">\n",
            ($U == "PROCEDURE"
                ? "<td>" .
                    html_select(
                        "fields[$t][inout]",
                        explode("|", driver()->inout),
                        $l["inout"],
                    )
                : "") . "<th>";
        if ($Rb) {
            echo "<input name='fields[$t][field]' value='" .
                h($l["field"]) .
                "' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'>";
        }
        echo input_hidden("fields[$t][orig]", $rf);
        edit_type("fields[$t]", $l, $b, $p);
        if ($U == "TABLE") {
            echo "<td>" .
            checkbox(
                "fields[$t][null]",
                1,
                $l["null"],
                "",
                "",
                "block",
                "label-null",
            ),
                "<td><label class='block'><input type='radio' name='auto_increment_col' value='$t'" .
                    ($l["auto_increment"] ? " checked" : "") .
                    " aria-labelledby='label-ai'></label>",
                "<td$Gb>" .
                    (driver()->generated
                        ? html_select(
                                "fields[$t][generated]",
                                array_merge(
                                    ["", "DEFAULT"],
                                    driver()->generated,
                                ),
                                $l["generated"],
                            ) . " "
                        : checkbox(
                            "fields[$t][generated]",
                            1,
                            $l["generated"],
                            "",
                            "",
                            "",
                            "label-default",
                        )),
                "<input name='fields[$t][default]' value='" .
                    h($l["default"]) .
                    "' aria-labelledby='label-default'>",
                support("comment")
                    ? "<td$gb><input name='fields[$t][comment]' value='" .
                        h($l["comment"]) .
                        "' data-maxlength='" .
                        (min_version(5.5) ? 1024 : 255) .
                        "' aria-labelledby='label-comment'>"
                    : "";
        }
        echo "<td>",
            support("move_col")
                ? icon("plus", "add[$t]", "+", "Add next") .
                    " " .
                    icon("up", "up[$t]", "↑", "Move up") .
                    " " .
                    icon("down", "down[$t]", "↓", "Move down") .
                    " "
                : "",
            $rf == "" || support("drop_col")
                ? icon("cross", "drop_col[$t]", "x", "Remove")
                : "";
    }
}
function process_fields(array &$m)
{
    $Xe = 0;
    if ($_POST["up"]) {
        $ae = 0;
        foreach ($m as $y => $l) {
            if (key($_POST["up"]) == $y) {
                unset($m[$y]);
                array_splice($m, $ae, 0, [$l]);
                break;
            }
            if (isset($l["field"])) {
                $ae = $Xe;
            }
            $Xe++;
        }
    } elseif ($_POST["down"]) {
        $Tc = false;
        foreach ($m as $y => $l) {
            if (isset($l["field"]) && $Tc) {
                unset($m[key($_POST["down"])]);
                array_splice($m, $Xe, 0, [$Tc]);
                break;
            }
            if (key($_POST["down"]) == $y) {
                $Tc = $l;
            }
            $Xe++;
        }
    } elseif ($_POST["add"]) {
        $m = array_values($m);
        array_splice($m, key($_POST["add"]), 0, [[]]);
    } elseif (!$_POST["drop_col"]) {
        return false;
    }
    return true;
}
function normalize_enum(array $B)
{
    $X = $B[0];
    return "'" .
        str_replace(
            "'",
            "''",
            addcslashes(
                stripcslashes(
                    str_replace($X[0] . $X[0], $X[0], substr($X, 1, -1)),
                ),
                "\\",
            ),
        ) .
        "'";
}
function grant($ad, array $ag, $d, $df)
{
    if (!$ag) {
        return true;
    }
    if ($ag == ["ALL PRIVILEGES", "GRANT OPTION"]) {
        return $ad == "GRANT"
            ? queries("$ad ALL PRIVILEGES$df WITH GRANT OPTION")
            : queries("$ad ALL PRIVILEGES$df") &&
                    queries("$ad GRANT OPTION$df");
    }
    return queries(
        "$ad " .
            preg_replace(
                "~(GRANT OPTION)\([^)]*\)~",
                '\1',
                implode("$d, ", $ag) . $d,
            ) .
            $df,
    );
}
function drop_create($Vb, $g, $Wb, $_h, $Xb, $A, $Be, $_e, $Ae, $af, $Oe)
{
    if ($_POST["drop"]) {
        query_redirect($Vb, $A, $Be);
    } elseif ($af == "") {
        query_redirect($g, $A, $Ae);
    } elseif ($af != $Oe) {
        $tb = queries($g);
        queries_redirect($A, $_e, $tb && queries($Vb));
        if ($tb) {
            queries($Wb);
        }
    } else {
        queries_redirect(
            $A,
            $_e,
            queries($_h) && queries($Xb) && queries($Vb) && queries($g),
        );
    }
}
function create_trigger($df, array $L)
{
    $Eh =
        " $L[Timing] $L[Event]" .
        (preg_match("~ OF~", $L["Event"]) ? " $L[Of]" : "");
    return "CREATE TRIGGER " .
        idf_escape($L["Trigger"]) .
        (JUSH == "mssql" ? $df . $Eh : $Eh . $df) .
        rtrim(" $L[Type]\n$L[Statement]", ";") .
        ";";
}
function create_routine($yg, array $L)
{
    $Q = [];
    $m = (array) $L["fields"];
    ksort($m);
    foreach ($m as $l) {
        if ($l["field"] != "") {
            $Q[] =
                (preg_match("~^(" . driver()->inout . ")\$~", $l["inout"])
                    ? "$l[inout] "
                    : "") .
                idf_escape($l["field"]) .
                process_type($l, "CHARACTER SET");
        }
    }
    $Ib = rtrim($L["definition"], ";");
    return "CREATE $yg " .
        idf_escape(trim($L["name"])) .
        " (" .
        implode(", ", $Q) .
        ")" .
        ($yg == "FUNCTION"
            ? " RETURNS" . process_type($L["returns"], "CHARACTER SET")
            : "") .
        ($L["language"] ? " LANGUAGE $L[language]" : "") .
        (JUSH == "pgsql" ? " AS " . q($Ib) : "\n$Ib;");
}
function remove_definer($I)
{
    return preg_replace(
        "~^([A-Z =]+) DEFINER=`" .
            preg_replace("~@(.*)~", '`@`(%|\1)', logged_user()) .
            "`~",
        '\1',
        $I,
    );
}
function format_foreign_key(array $o)
{
    $i = $o["db"];
    $Te = $o["ns"];
    return " FOREIGN KEY (" .
        implode(", ", array_map("Adminer\idf_escape", $o["source"])) .
        ") REFERENCES " .
        ($i != "" && $i != $_GET["db"] ? idf_escape($i) . "." : "") .
        ($Te != "" && $Te != $_GET["ns"] ? idf_escape($Te) . "." : "") .
        idf_escape($o["table"]) .
        " (" .
        implode(", ", array_map("Adminer\idf_escape", $o["target"])) .
        ")" .
        (preg_match("~^(" . driver()->onActions . ")\$~", $o["on_delete"])
            ? " ON DELETE $o[on_delete]"
            : "") .
        (preg_match("~^(" . driver()->onActions . ")\$~", $o["on_update"])
            ? " ON UPDATE $o[on_update]"
            : "");
}
function tar_file($n, $Jh)
{
    $K = pack(
        "a100a8a8a8a12a12",
        $n,
        644,
        0,
        0,
        decoct($Jh->size),
        decoct(time()),
    );
    $Ua = 8 * 32;
    for ($t = 0; $t < strlen($K); $t++) {
        $Ua += ord($K[$t]);
    }
    $K .= sprintf("%06o", $Ua) . "\0 ";
    echo $K, str_repeat("\0", 512 - strlen($K));
    $Jh->send();
    echo str_repeat("\0", 511 - (($Jh->size + 511) % 512));
}
function ini_bytes($Dd)
{
    $X = ini_get($Dd);
    switch (strtolower(substr($X, -1))) {
        case "g":
            $X = (int) $X * 1024;
        case "m":
            $X = (int) $X * 1024;
        case "k":
            $X = (int) $X * 1024;
    }
    return $X;
}
function doc_link(array $Hf, $Ah = "<sup>?</sup>")
{
    $Lg = connection()->server_info;
    $ri = preg_replace("~^(\d\.?\d).*~s", '\1', $Lg);
    $ii = [
        "sql" => "https://dev.mysql.com/doc/refman/$ri/en/",
        "sqlite" => "https://www.sqlite.org/",
        "pgsql" =>
            "https://www.postgresql.org/docs/" .
            (connection()->flavor == "cockroach" ? "current" : $ri) .
            "/",
        "mssql" => "https://learn.microsoft.com/en-us/sql/",
        "oracle" =>
            "https://www.oracle.com/pls/topic/lookup?ctx=db" .
            preg_replace("~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s", '\1\2', $Lg) .
            "&id=",
    ];
    if (connection()->flavor == "maria") {
        $ii["sql"] = "https://mariadb.com/kb/en/";
        $Hf["sql"] = isset($Hf["mariadb"])
            ? $Hf["mariadb"]
            : str_replace(".html", "/", $Hf["sql"]);
    }
    return $Hf[JUSH]
        ? "<a href='" .
                h(
                    $ii[JUSH] .
                        $Hf[JUSH] .
                        (JUSH == "mssql" ? "?view=sql-server-ver$ri" : ""),
                ) .
                "'" .
                target_blank() .
                ">$Ah</a>"
        : "";
}
function db_size($i)
{
    if (!connection()->select_db($i)) {
        return "?";
    }
    $K = 0;
    foreach (table_status() as $S) {
        $K += $S["Data_length"] + $S["Index_length"];
    }
    return format_number($K);
}
function set_utf8mb4($g)
{
    static $Q = false;
    if (!$Q && preg_match("~\butf8mb4~i", $g)) {
        $Q = true;
        echo "SET NAMES " . charset(connection()) . ";\n\n";
    }
}
if (isset($_GET["status"])) {
    $_GET["variables"] = $_GET["status"];
}
if (isset($_GET["import"])) {
    $_GET["sql"] = $_GET["import"];
}
if (
    !(DB != ""
        ? connection()->select_db(DB)
        : isset($_GET["sql"]) ||
            isset($_GET["dump"]) ||
            isset($_GET["database"]) ||
            isset($_GET["processlist"]) ||
            isset($_GET["privileges"]) ||
            isset($_GET["user"]) ||
            isset($_GET["variables"]) ||
            $_GET["script"] == "connect" ||
            $_GET["script"] == "kill")
) {
    if (DB != "" || $_GET["refresh"]) {
        restart_session();
        set_session("dbs", null);
    }
    if (DB != "") {
        header("HTTP/1.1 404 Not Found");
        page_header("Database" . ": " . h(DB), "Invalid database.", true);
    } else {
        if ($_POST["db"] && !$k) {
            queries_redirect(
                substr(ME, 0, -1),
                "Databases have been dropped.",
                drop_databases($_POST["db"]),
            );
        }
        page_header("Select database", $k, false);
        echo "<p class='links'>\n";
        foreach (
            [
                "database" => "Create database",
                "privileges" => "Privileges",
                "processlist" => "Process list",
                "variables" => "Variables",
                "status" => "Status",
            ]
            as $y => $X
        ) {
            if (support($y)) {
                echo "<a href='" . h(ME) . "$y='>$X</a>\n";
            }
        }
        echo "<p>" .
        sprintf(
            "%s version: %s through PHP extension %s",
            get_driver(DRIVER),
            "<b>" . h(connection()->server_info) . "</b>",
            "<b>" . connection()->extension . "</b>",
        ) .
        "\n",
            "<p>" .
                sprintf("Logged as: %s", "<b>" . h(logged_user()) . "</b>") .
                "\n";
        $h = adminer()->databases();
        if ($h) {
            $Cg = support("scheme");
            $b = collations();
            echo "<form action='' method='post'>\n",
                "<table class='checkable odds'>\n",
                script(
                    "mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});",
                ),
                "<thead><tr>" .
                    (support("database") ? "<td>" : "") .
                    "<th>" .
                    "Database" .
                    (get_session("dbs") !== null
                        ? " - <a href='" .
                            h(ME) .
                            "refresh=1'>" .
                            "Refresh" .
                            "</a>"
                        : "") .
                    "<td>" .
                    "Collation" .
                    "<td>" .
                    "Tables" .
                    "<td>" .
                    "Size" .
                    " - <a href='" .
                    h(ME) .
                    "dbsize=1'>" .
                    "Compute" .
                    "</a>" .
                    script(
                        "qsl('a').onclick = partial(ajaxSetHtml, '" .
                            js_escape(ME) .
                            "script=connect');",
                        "",
                    ) .
                    "</thead>\n";
            $h = $_GET["dbsize"] ? count_tables($h) : array_flip($h);
            foreach ($h as $i => $T) {
                $xg = h(ME) . "db=" . urlencode($i);
                $u = h("Db-" . $i);
                echo "<tr>" .
                (support("database")
                    ? "<td>" .
                        checkbox(
                            "db[]",
                            $i,
                            in_array($i, (array) $_POST["db"]),
                            "",
                            "",
                            "",
                            $u,
                        )
                    : ""),
                    "<th><a href='$xg' id='$u'>" . h($i) . "</a>";
                $bb = h(db_collation($i, $b));
                echo "<td>" .
                (support("database")
                    ? "<a href='$xg" .
                        ($Cg ? "&amp;ns=" : "") .
                        "&amp;database=' title='" .
                        "Alter database" .
                        "'>$bb</a>"
                    : $bb),
                    "<td align='right'><a href='$xg&amp;schema=' id='tables-" .
                        h($i) .
                        "' title='" .
                        "Database schema" .
                        "'>" .
                        ($_GET["dbsize"] ? $T : "?") .
                        "</a>",
                    "<td align='right' id='size-" .
                        h($i) .
                        "'>" .
                        ($_GET["dbsize"] ? db_size($i) : "?"),
                    "\n";
            }
            echo "</table>\n",
                support("database")
                    ? "<div class='footer'><div>\n" .
                        "<fieldset><legend>" .
                        "Selected" .
                        " <span id='selected'></span></legend><div>\n" .
                        input_hidden("all") .
                        script(
                            "qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };",
                        ) .
                        "<input type='submit' name='drop' value='" .
                        "Drop" .
                        "'>" .
                        confirm() .
                        "\n" .
                        "</div></fieldset>\n" .
                        "</div></div>\n"
                    : "",
                input_token(),
                "</form>\n",
                script("tableCheck();");
        }
        if (!empty(adminer()->plugins)) {
            echo "<div class='plugins'>\n",
                "<h3>" . "Loaded plugins" . "</h3>\n<ul>\n";
            foreach (adminer()->plugins as $Mf) {
                $Mb = method_exists($Mf, "description")
                    ? $Mf->description()
                    : "";
                if (!$Mb) {
                    $og = new \ReflectionObject($Mf);
                    if (
                        preg_match("~^/[\s*]+(.+)~", $og->getDocComment(), $B)
                    ) {
                        $Mb = $B[1];
                    }
                }
                $Dg = method_exists($Mf, "screenshot") ? $Mf->screenshot() : "";
                echo "<li><b>" .
                    get_class($Mf) .
                    "</b>" .
                    h($Mb ? ": $Mb" : "") .
                    ($Dg
                        ? " (<a href='" .
                            h($Dg) .
                            "'" .
                            target_blank() .
                            ">" .
                            "screenshot" .
                            "</a>)"
                        : "") .
                    "\n";
            }
            echo "</ul>\n";
            adminer()->pluginsLinks();
            echo "</div>\n";
        }
    }
    page_footer("db");
    exit();
}
class TmpFile
{
    private $handler;
    var $size;
    function __construct()
    {
        $this->handler = tmpfile();
    }
    function write($nb)
    {
        $this->size += strlen($nb);
        fwrite($this->handler, $nb);
    }
    function send()
    {
        fseek($this->handler, 0);
        fpassthru($this->handler);
        fclose($this->handler);
    }
}
if (
    isset($_GET["select"]) &&
    ($_POST["edit"] || $_POST["clone"]) &&
    !$_POST["save"]
) {
    $_GET["edit"] = $_GET["select"];
}
if (isset($_GET["callf"])) {
    $_GET["call"] = $_GET["callf"];
}
if (isset($_GET["function"])) {
    $_GET["procedure"] = $_GET["function"];
}
if (isset($_GET["download"])) {
    $a = $_GET["download"];
    $m = fields($a);
    header("Content-Type: application/octet-stream");
    header(
        "Content-Disposition: attachment; filename=" .
            friendly_url("$a-" . implode("_", $_GET["where"])) .
            "." .
            friendly_url($_GET["field"]),
    );
    $O = [idf_escape($_GET["field"])];
    $J = driver()->select($a, $O, [where($_GET, $m)], $O);
    $L = $J ? $J->fetch_row() : [];
    echo driver()->value($L[0], $m[$_GET["field"]]);
    exit();
} elseif (isset($_GET["table"])) {
    $a = $_GET["table"];
    $m = fields($a);
    if (!$m) {
        $k = error() ?: "No tables.";
    }
    $S = table_status1($a);
    $D = adminer()->tableName($S);
    page_header(
        ($m && is_view($S)
            ? ($S["Engine"] == "materialized view"
                ? "Materialized view"
                : "View")
            : "Table") .
            ": " .
            ($D != "" ? $D : h($a)),
        $k,
    );
    $wg = [];
    foreach ($m as $y => $l) {
        $wg += $l["privileges"];
    }
    adminer()->selectLinks(
        $S,
        isset($wg["insert"]) || !support("table") ? "" : null,
    );
    $fb = $S["Comment"];
    if ($fb != "") {
        echo "<p class='nowrap'>" . "Comment" . ": " . h($fb) . "\n";
    }
    if ($m) {
        adminer()->tableStructurePrint($m, $S);
    }
    if (support("indexes") && driver()->supportsIndex($S)) {
        echo "<h3 id='indexes'>" . "Indexes" . "</h3>\n";
        $x = indexes($a);
        if ($x) {
            adminer()->tableIndexesPrint($x);
        }
        echo '<p class="links"><a href="' .
            h(ME) .
            "indexes=" .
            urlencode($a) .
            '">' .
            "Alter indexes" .
            "</a>\n";
    }
    if (!is_view($S)) {
        if (fk_support($S)) {
            echo "<h3 id='foreign-keys'>" . "Foreign keys" . "</h3>\n";
            $p = foreign_keys($a);
            if ($p) {
                echo "<table>\n",
                    "<thead><tr><th>" .
                        "Source" .
                        "<td>" .
                        "Target" .
                        "<td>" .
                        "ON DELETE" .
                        "<td>" .
                        "ON UPDATE" .
                        "<td></thead>\n";
                foreach ($p as $D => $o) {
                    echo "<tr title='" . h($D) . "'>",
                        "<th><i>" .
                            implode(
                                "</i>, <i>",
                                array_map("Adminer\h", $o["source"]),
                            ) .
                            "</i>";
                    $_ =
                        $o["db"] != ""
                            ? preg_replace(
                                "~db=[^&]*~",
                                "db=" . urlencode($o["db"]),
                                ME,
                            )
                            : ($o["ns"] != ""
                                ? preg_replace(
                                    "~ns=[^&]*~",
                                    "ns=" . urlencode($o["ns"]),
                                    ME,
                                )
                                : ME);
                    echo "<td><a href='" .
                    h($_ . "table=" . urlencode($o["table"])) .
                    "'>" .
                    ($o["db"] != "" && $o["db"] != DB
                        ? "<b>" . h($o["db"]) . "</b>."
                        : "") .
                    ($o["ns"] != "" && $o["ns"] != $_GET["ns"]
                        ? "<b>" . h($o["ns"]) . "</b>."
                        : "") .
                    h($o["table"]) .
                    "</a>",
                        "(<i>" .
                            implode(
                                "</i>, <i>",
                                array_map("Adminer\h", $o["target"]),
                            ) .
                            "</i>)",
                        "<td>" . h($o["on_delete"]),
                        "<td>" . h($o["on_update"]),
                        '<td><a href="' .
                            h(
                                ME .
                                    "foreign=" .
                                    urlencode($a) .
                                    "&name=" .
                                    urlencode($D),
                            ) .
                            '">' .
                            "Alter" .
                            "</a>",
                        "\n";
                }
                echo "</table>\n";
            }
            echo '<p class="links"><a href="' .
                h(ME) .
                "foreign=" .
                urlencode($a) .
                '">' .
                "Add foreign key" .
                "</a>\n";
        }
        if (support("check")) {
            echo "<h3 id='checks'>" . "Checks" . "</h3>\n";
            $Ra = driver()->checkConstraints($a);
            if ($Ra) {
                echo "<table>\n";
                foreach ($Ra as $y => $X) {
                    echo "<tr title='" . h($y) . "'>",
                        "<td><code class='jush-" . JUSH . "'>" . h($X),
                        "<td><a href='" .
                            h(
                                ME .
                                    "check=" .
                                    urlencode($a) .
                                    "&name=" .
                                    urlencode($y),
                            ) .
                            "'>" .
                            "Alter" .
                            "</a>",
                        "\n";
                }
                echo "</table>\n";
            }
            echo '<p class="links"><a href="' .
                h(ME) .
                "check=" .
                urlencode($a) .
                '">' .
                "Create check" .
                "</a>\n";
        }
    }
    if (support(is_view($S) ? "view_trigger" : "trigger")) {
        echo "<h3 id='triggers'>" . "Triggers" . "</h3>\n";
        $Uh = triggers($a);
        if ($Uh) {
            echo "<table>\n";
            foreach ($Uh as $y => $X) {
                echo "<tr valign='top'><td>" .
                    h($X[0]) .
                    "<td>" .
                    h($X[1]) .
                    "<th>" .
                    h($y) .
                    "<td><a href='" .
                    h(
                        ME .
                            "trigger=" .
                            urlencode($a) .
                            "&name=" .
                            urlencode($y),
                    ) .
                    "'>" .
                    "Alter" .
                    "</a>\n";
            }
            echo "</table>\n";
        }
        echo '<p class="links"><a href="' .
            h(ME) .
            "trigger=" .
            urlencode($a) .
            '">' .
            "Add trigger" .
            "</a>\n";
    }
} elseif (isset($_GET["schema"])) {
    page_header(
        "Database schema",
        "",
        [],
        h(DB . ($_GET["ns"] ? ".$_GET[ns]" : "")),
    );
    $qh = [];
    $rh = [];
    $ca =
        $_GET["schema"] ?:
        $_COOKIE["adminer_schema-" . str_replace(".", "_", DB)];
    preg_match_all(
        '~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',
        $ca,
        $qe,
        PREG_SET_ORDER,
    );
    foreach ($qe as $t => $B) {
        $qh[$B[1]] = [$B[2], $B[3]];
        $rh[] = "\n\t'" . js_escape($B[1]) . "': [ $B[2], $B[3] ]";
    }
    $Mh = 0;
    $Ca = -1;
    $Bg = [];
    $ng = [];
    $ee = [];
    $na = driver()->allFields();
    foreach (table_status("", true) as $R => $S) {
        if (is_view($S)) {
            continue;
        }
        $Pf = 0;
        $Bg[$R]["fields"] = [];
        foreach ($na[$R] as $l) {
            $Pf += 1.25;
            $l["pos"] = $Pf;
            $Bg[$R]["fields"][$l["field"]] = $l;
        }
        $Bg[$R]["pos"] = $qh[$R] ?: [$Mh, 0];
        foreach (adminer()->foreignKeys($R) as $X) {
            if (!$X["db"]) {
                $ce = $Ca;
                if (idx($qh[$R], 1) || idx($qh[$X["table"]], 1)) {
                    $ce =
                        min(idx($qh[$R], 1, 0), idx($qh[$X["table"]], 1, 0)) -
                        1;
                } else {
                    $Ca -= 0.1;
                }
                while ($ee[(string) $ce]) {
                    $ce -= 0.0001;
                }
                $Bg[$R]["references"][$X["table"]][(string) $ce] = [
                    $X["source"],
                    $X["target"],
                ];
                $ng[$X["table"]][$R][(string) $ce] = $X["target"];
                $ee[(string) $ce] = true;
            }
        }
        $Mh = max($Mh, $Bg[$R]["pos"][0] + 2.5 + $Pf);
    }
    echo '<div id="schema" style="height: ',
        $Mh,
        'em;">
<script',
        nonce(),
        '>
qs(\'#schema\').onselectstart = () => false;
const tablePos = {',
        implode(",", $rh) . "\n",
        '};
const em = qs(\'#schema\').offsetHeight / ',
        $Mh,
        ';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',
        js_escape(DB),
        '\');
</script>
';
    foreach ($Bg as $D => $R) {
        echo "<div class='table' style='top: " .
        $R["pos"][0] .
        "em; left: " .
        $R["pos"][1] .
        "em;'>",
            '<a href="' .
                h(ME) .
                "table=" .
                urlencode($D) .
                '"><b>' .
                h($D) .
                "</b></a>",
            script("qsl('div').onmousedown = schemaMousedown;");
        foreach ($R["fields"] as $l) {
            $X =
                "<span" .
                type_class($l["type"]) .
                ' title="' .
                h(
                    $l["type"] .
                        ($l["length"] ? "($l[length])" : "") .
                        ($l["null"] ? " NULL" : ""),
                ) .
                '">' .
                h($l["field"]) .
                "</span>";
            echo "<br>" . ($l["primary"] ? "<i>$X</i>" : $X);
        }
        foreach ((array) $R["references"] as $yh => $pg) {
            foreach ($pg as $ce => $kg) {
                $de = $ce - idx($qh[$D], 1);
                $t = 0;
                foreach ($kg[0] as $Vg) {
                    echo "\n<div class='references' title='" .
                        h($yh) .
                        "' id='refs$ce-" .
                        $t++ .
                        "' style='left: $de" .
                        "em; top: " .
                        $R["fields"][$Vg]["pos"] .
                        "em; padding-top: .5em;'>" .
                        "<div style='border-top: 1px solid gray; width: " .
                        -$de .
                        "em;'></div></div>";
                }
            }
        }
        foreach ((array) $ng[$D] as $yh => $pg) {
            foreach ($pg as $ce => $d) {
                $de = $ce - idx($qh[$D], 1);
                $t = 0;
                foreach ($d as $xh) {
                    echo "\n<div class='references arrow' title='" .
                        h($yh) .
                        "' id='refd$ce-" .
                        $t++ .
                        "' style='left: $de" .
                        "em; top: " .
                        $R["fields"][$xh]["pos"] .
                        "em;'>" .
                        "<div style='height: .5em; border-bottom: 1px solid gray; width: " .
                        -$de .
                        "em;'></div>" .
                        "</div>";
                }
            }
        }
        echo "\n</div>\n";
    }
    foreach ($Bg as $D => $R) {
        foreach ((array) $R["references"] as $yh => $pg) {
            foreach ($pg as $ce => $kg) {
                $Ee = $Mh;
                $ue = -10;
                foreach ($kg[0] as $y => $Vg) {
                    $Qf = $R["pos"][0] + $R["fields"][$Vg]["pos"];
                    $Rf =
                        $Bg[$yh]["pos"][0] +
                        $Bg[$yh]["fields"][$kg[1][$y]]["pos"];
                    $Ee = min($Ee, $Qf, $Rf);
                    $ue = max($ue, $Qf, $Rf);
                }
                echo "<div class='references' id='refl$ce' style='left: $ce" .
                    "em; top: $Ee" .
                    "em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: " .
                    ($ue - $Ee) .
                    "em;'></div></div>\n";
            }
        }
    }
    echo '</div>
<p class="links"><a href="',
        h(ME . "schema=" . urlencode($ca)),
        '" id="schema-link">Permanent link</a>
';
} elseif (isset($_GET["dump"])) {
    $a = $_GET["dump"];
    if ($_POST && !$k) {
        save_settings(
            array_intersect_key(
                $_POST,
                array_flip([
                    "output",
                    "format",
                    "db_style",
                    "types",
                    "routines",
                    "events",
                    "table_style",
                    "auto_increment",
                    "triggers",
                    "data_style",
                ]),
            ),
            "adminer_export",
        );
        $T =
            array_flip((array) $_POST["tables"]) +
            array_flip((array) $_POST["data"]);
        $_c = dump_headers(
            count($T) == 1 ? key($T) : DB,
            DB == "" || count($T) > 1,
        );
        $Od = preg_match("~sql~", $_POST["format"]);
        if ($Od) {
            echo "-- Adminer " .
                VERSION .
                " " .
                get_driver(DRIVER) .
                " " .
                str_replace("\n", " ", connection()->server_info) .
                " dump\n\n";
            if (JUSH == "sql") {
                echo "SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
" .
                    ($_POST["data_style"]
                        ? "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
"
                        : "") .
                    "
";
                connection()->query("SET time_zone = '+00:00'");
                connection()->query("SET sql_mode = ''");
            }
        }
        $ih = $_POST["db_style"];
        $h = [DB];
        if (DB == "") {
            $h = $_POST["databases"];
            if (is_string($h)) {
                $h = explode("\n", rtrim(str_replace("\r", "", $h), "\n"));
            }
        }
        foreach ((array) $h as $i) {
            adminer()->dumpDatabase($i);
            if (connection()->select_db($i)) {
                if (
                    $Od &&
                    preg_match("~CREATE~", $ih) &&
                    ($g = get_val("SHOW CREATE DATABASE " . idf_escape($i), 1))
                ) {
                    set_utf8mb4($g);
                    if ($ih == "DROP+CREATE") {
                        echo "DROP DATABASE IF EXISTS " .
                            idf_escape($i) .
                            ";\n";
                    }
                    echo "$g;\n";
                }
                if ($Od) {
                    if ($ih) {
                        echo use_sql($i) . ";\n\n";
                    }
                    $xf = "";
                    if ($_POST["types"]) {
                        foreach (types() as $u => $U) {
                            $oc = type_values($u);
                            if ($oc) {
                                $xf .=
                                    ($ih != "DROP+CREATE"
                                        ? "DROP TYPE IF EXISTS " .
                                            idf_escape($U) .
                                            ";;\n"
                                        : "") .
                                    "CREATE TYPE " .
                                    idf_escape($U) .
                                    " AS ENUM ($oc);\n\n";
                            } else {
                                $xf .= "-- Could not export type $U\n\n";
                            }
                        }
                    }
                    if ($_POST["routines"]) {
                        foreach (routines() as $L) {
                            $D = $L["ROUTINE_NAME"];
                            $yg = $L["ROUTINE_TYPE"];
                            $g = create_routine(
                                $yg,
                                ["name" => $D] +
                                    routine($L["SPECIFIC_NAME"], $yg),
                            );
                            set_utf8mb4($g);
                            $xf .=
                                ($ih != "DROP+CREATE"
                                    ? "DROP $yg IF EXISTS " .
                                        idf_escape($D) .
                                        ";;\n"
                                    : "") . "$g;\n\n";
                        }
                    }
                    if ($_POST["events"]) {
                        foreach (get_rows("SHOW EVENTS", null, "-- ") as $L) {
                            $g = remove_definer(
                                get_val(
                                    "SHOW CREATE EVENT " .
                                        idf_escape($L["Name"]),
                                    3,
                                ),
                            );
                            set_utf8mb4($g);
                            $xf .=
                                ($ih != "DROP+CREATE"
                                    ? "DROP EVENT IF EXISTS " .
                                        idf_escape($L["Name"]) .
                                        ";;\n"
                                    : "") . "$g;;\n\n";
                        }
                    }
                    echo $xf && JUSH == "sql"
                        ? "DELIMITER ;;\n\n$xf" . "DELIMITER ;\n\n"
                        : $xf;
                }
                if ($_POST["table_style"] || $_POST["data_style"]) {
                    $ti = [];
                    foreach (table_status("", true) as $D => $S) {
                        $R = DB == "" || in_array($D, (array) $_POST["tables"]);
                        $_b = DB == "" || in_array($D, (array) $_POST["data"]);
                        if ($R || $_b) {
                            $Jh = null;
                            if ($_c == "tar") {
                                $Jh = new TmpFile();
                                ob_start([$Jh, "write"], 1e5);
                            }
                            adminer()->dumpTable(
                                $D,
                                $R ? $_POST["table_style"] : "",
                                is_view($S) ? 2 : 0,
                            );
                            if (is_view($S)) {
                                $ti[] = $D;
                            } elseif ($_b) {
                                $m = fields($D);
                                adminer()->dumpData(
                                    $D,
                                    $_POST["data_style"],
                                    "SELECT *" .
                                        convert_fields($m, $m) .
                                        " FROM " .
                                        table($D),
                                );
                            }
                            if (
                                $Od &&
                                $_POST["triggers"] &&
                                $R &&
                                ($Uh = trigger_sql($D))
                            ) {
                                echo "\nDELIMITER ;;\n$Uh\nDELIMITER ;\n";
                            }
                            if ($_c == "tar") {
                                ob_end_flush();
                                tar_file(
                                    (DB != "" ? "" : "$i/") . "$D.csv",
                                    $Jh,
                                );
                            } elseif ($Od) {
                                echo "\n";
                            }
                        }
                    }
                    if (function_exists('Adminer\foreign_keys_sql')) {
                        foreach (table_status("", true) as $D => $S) {
                            $R =
                                DB == "" ||
                                in_array($D, (array) $_POST["tables"]);
                            if ($R && !is_view($S)) {
                                echo foreign_keys_sql($D);
                            }
                        }
                    }
                    foreach ($ti as $si) {
                        adminer()->dumpTable($si, $_POST["table_style"], 1);
                    }
                    if ($_c == "tar") {
                        echo pack("x512");
                    }
                }
            }
        }
        adminer()->dumpFooter();
        exit();
    }
    page_header(
        "Export",
        $k,
        $_GET["export"] != "" ? ["table" => $_GET["export"]] : [],
        h(DB),
    );
    echo '
<form action="" method="post">
<table class="layout">
';
    $Db = ["", "USE", "DROP+CREATE", "CREATE"];
    $sh = ["", "DROP+CREATE", "CREATE"];
    $Ab = ["", "TRUNCATE+INSERT", "INSERT"];
    if (JUSH == "sql") {
        $Ab[] = "INSERT+UPDATE";
    }
    $L = get_settings("adminer_export");
    if (!$L) {
        $L = [
            "output" => "text",
            "format" => "sql",
            "db_style" => DB != "" ? "" : "CREATE",
            "table_style" => "DROP+CREATE",
            "data_style" => "INSERT",
        ];
    }
    if (!isset($L["events"])) {
        $L["routines"] = $L["events"] = $_GET["dump"] == "";
        $L["triggers"] = $L["table_style"];
    }
    echo "<tr><th>" .
    "Output" .
    "<td>" .
    html_radios("output", adminer()->dumpOutput(), $L["output"]) .
    "\n",
        "<tr><th>" .
            "Format" .
            "<td>" .
            html_radios("format", adminer()->dumpFormat(), $L["format"]) .
            "\n",
        JUSH == "sqlite"
            ? ""
            : "<tr><th>" .
                "Database" .
                "<td>" .
                html_select("db_style", $Db, $L["db_style"]) .
                (support("type")
                    ? checkbox("types", 1, $L["types"], "User types")
                    : "") .
                (support("routine")
                    ? checkbox("routines", 1, $L["routines"], "Routines")
                    : "") .
                (support("event")
                    ? checkbox("events", 1, $L["events"], "Events")
                    : ""),
        "<tr><th>" .
            "Tables" .
            "<td>" .
            html_select("table_style", $sh, $L["table_style"]) .
            checkbox(
                "auto_increment",
                1,
                $L["auto_increment"],
                "Auto Increment",
            ) .
            (support("trigger")
                ? checkbox("triggers", 1, $L["triggers"], "Triggers")
                : ""),
        "<tr><th>" .
            "Data" .
            "<td>" .
            html_select("data_style", $Ab, $L["data_style"]),
        '</table>
<p><input type="submit" value="Export">
',
        input_token(),
        '
<table>
',
        script("qsl('table').onclick = dumpClick;");
    $Vf = [];
    if (DB != "") {
        $Sa = $a != "" ? "" : " checked";
        echo "<thead><tr>",
            "<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$Sa>" .
                "Tables" .
                "</label>" .
                script(
                    "qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",
                    "",
                ),
            "<th style='text-align: right;'><label class='block'>" .
                "Data" .
                "<input type='checkbox' id='check-data'$Sa></label>" .
                script(
                    "qs('#check-data').onclick = partial(formCheck, /^data\\[/);",
                    "",
                ),
            "</thead>\n";
        $ti = "";
        $uh = tables_list();
        foreach ($uh as $D => $U) {
            $Uf = preg_replace("~_.*~", "", $D);
            $Sa = $a == "" || $a == (substr($a, -1) == "%" ? "$Uf%" : $D);
            $Xf = "<tr><td>" . checkbox("tables[]", $D, $Sa, $D, "", "block");
            if ($U !== null && !preg_match("~table~i", $U)) {
                $ti .= "$Xf\n";
            } else {
                echo "$Xf<td align='right'><label class='block'><span id='Rows-" .
                    h($D) .
                    "'></span>" .
                    checkbox("data[]", $D, $Sa) .
                    "</label>\n";
            }
            $Vf[$Uf]++;
        }
        echo $ti;
        if ($uh) {
            echo script("ajaxSetHtml('" . js_escape(ME) . "script=db');");
        }
    } else {
        echo "<thead><tr><th style='text-align: left;'>",
            "<label class='block'><input type='checkbox' id='check-databases'" .
                ($a == "" ? " checked" : "") .
                ">" .
                "Database" .
                "</label>",
            script(
                "qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",
                "",
            ),
            "</thead>\n";
        $h = adminer()->databases();
        if ($h) {
            foreach ($h as $i) {
                if (!information_schema($i)) {
                    $Uf = preg_replace("~_.*~", "", $i);
                    echo "<tr><td>" .
                        checkbox(
                            "databases[]",
                            $i,
                            $a == "" || $a == "$Uf%",
                            $i,
                            "",
                            "block",
                        ) .
                        "\n";
                    $Vf[$Uf]++;
                }
            }
        } else {
            echo "<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";
        }
    }
    echo '</table>
</form>
';
    $Lc = true;
    foreach ($Vf as $y => $X) {
        if ($y != "" && $X > 1) {
            echo ($Lc ? "<p>" : " ") .
                "<a href='" .
                h(ME) .
                "dump=" .
                urlencode("$y%") .
                "'>" .
                h($y) .
                "</a>";
            $Lc = false;
        }
    }
} elseif (isset($_GET["privileges"])) {
    page_header("Privileges");
    echo '<p class="links"><a href="' .
        h(ME) .
        'user=">' .
        "Create user" .
        "</a>";
    $J = connection()->query(
        "SELECT User, Host FROM mysql." .
            (DB == "" ? "user" : "db WHERE " . q(DB) . " LIKE Db") .
            " ORDER BY Host, User",
    );
    $ad = $J;
    if (!$J) {
        $J = connection()->query(
            "SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host",
        );
    }
    echo "<form action=''><p>\n";
    hidden_fields_get();
    echo input_hidden("db", DB),
        $ad ? "" : input_hidden("grant"),
        "<table class='odds'>\n",
        "<thead><tr><th>" . "Username" . "<th>" . "Server" . "<th></thead>\n";
    while ($L = $J->fetch_assoc()) {
        echo "<tr><td>" .
            h($L["User"]) .
            "<td>" .
            h($L["Host"]) .
            '<td><a href="' .
            h(
                ME .
                    "user=" .
                    urlencode($L["User"]) .
                    "&host=" .
                    urlencode($L["Host"]),
            ) .
            '">' .
            "Edit" .
            "</a>\n";
    }
    if (!$ad || DB != "") {
        echo "<tr><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='" .
            "Edit" .
            "'>\n";
    }
    echo "</table>\n", "</form>\n";
} elseif (isset($_GET["sql"])) {
    if (!$k && $_POST["export"]) {
        save_settings(
            ["output" => $_POST["output"], "format" => $_POST["format"]],
            "adminer_import",
        );
        dump_headers("sql");
        adminer()->dumpTable("", "");
        adminer()->dumpData("", "table", $_POST["query"]);
        adminer()->dumpFooter();
        exit();
    }
    restart_session();
    $pd = &get_session("queries");
    $od = &$pd[DB];
    if (!$k && $_POST["clear"]) {
        $od = [];
        redirect(remove_from_uri("history"));
    }
    stop_session();
    page_header(isset($_GET["import"]) ? "Import" : "SQL command", $k);
    $je = "--" . (JUSH == "sql" ? " " : "");
    if (!$k && $_POST) {
        $q = false;
        if (!isset($_GET["import"])) {
            $I = $_POST["query"];
        } elseif ($_POST["webfile"]) {
            $Yg = adminer()->importServerPath();
            $q = @fopen(
                file_exists($Yg) ? $Yg : "compress.zlib://$Yg.gz",
                "rb",
            );
            $I = $q ? fread($q, 1e6) : false;
        } else {
            $I = get_file("sql_file", true, ";");
        }
        if (is_string($I)) {
            if (
                function_exists("memory_get_usage") &&
                ($ze = ini_bytes("memory_limit")) != "-1"
            ) {
                @ini_set(
                    "memory_limit",
                    max($ze, strval(2 * strlen($I) + memory_get_usage() + 8e6)),
                );
            }
            if ($I != "" && strlen($I) < 1e6) {
                $H = $I . (preg_match("~;[ \t\r\n]*\$~", $I) ? "" : ";");
                if (!$od || first(end($od)) != $H) {
                    restart_session();
                    $od[] = [$H, time()];
                    set_session("queries", $pd);
                    stop_session();
                }
            }
            $Wg = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|$je)[^\n]*\n?|--\r?\n)";
            $Kb = ";";
            $Xe = 0;
            $ic = true;
            $f = connect();
            if ($f && DB != "") {
                $f->select_db(DB);
                if ($_GET["ns"] != "") {
                    set_schema($_GET["ns"], $f);
                }
            }
            $eb = 0;
            $qc = [];
            $Bf =
                '[\'"' .
                (JUSH == "sql"
                    ? "`#"
                    : (JUSH == "sqlite"
                        ? "`["
                        : (JUSH == "mssql"
                            ? "["
                            : ""))) .
                "]|/\*|" .
                $je .
                '|$' .
                (JUSH == "pgsql" ? '|\$[^$]*\$' : "");
            $Nh = microtime(true);
            $ha = get_settings("adminer_import");
            $Zb = adminer()->dumpFormat();
            unset($Zb["sql"]);
            while ($I != "") {
                if (
                    !$Xe &&
                    preg_match("~^$Wg*+DELIMITER\\s+(\\S+)~i", $I, $B)
                ) {
                    $Kb = preg_quote($B[1]);
                    $I = substr($I, strlen($B[0]));
                } elseif (
                    !$Xe &&
                    JUSH == "pgsql" &&
                    preg_match(
                        "~^($Wg*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",
                        $I,
                        $B,
                    )
                ) {
                    $Kb = "\n\\\\\\.\r?\n";
                    $Xe = strlen($B[0]);
                } else {
                    preg_match(
                        "($Kb\\s*|$Bf)",
                        $I,
                        $B,
                        PREG_OFFSET_CAPTURE,
                        $Xe,
                    );
                    [$Tc, $Pf] = $B[0];
                    if (!$Tc && $q && !feof($q)) {
                        $I .= fread($q, 1e5);
                    } else {
                        if (!$Tc && rtrim($I) == "") {
                            break;
                        }
                        $Xe = $Pf + strlen($Tc);
                        if ($Tc && !preg_match("(^$Kb)", $Tc)) {
                            $La =
                                driver()->hasCStyleEscapes() ||
                                (JUSH == "pgsql" &&
                                    ($Pf > 0 &&
                                        strtolower($I[$Pf - 1]) == "e"));
                            $If =
                                $Tc == "/*"
                                    ? "\*/"
                                    : ($Tc == "["
                                        ? "]"
                                        : (preg_match("~^$je|^#~", $Tc)
                                            ? "\n"
                                            : preg_quote($Tc) .
                                                ($La ? "|\\\\." : "")));
                            while (
                                preg_match(
                                    "($If|\$)s",
                                    $I,
                                    $B,
                                    PREG_OFFSET_CAPTURE,
                                    $Xe,
                                )
                            ) {
                                $N = $B[0][0];
                                if (!$N && $q && !feof($q)) {
                                    $I .= fread($q, 1e5);
                                } else {
                                    $Xe = $B[0][1] + strlen($N);
                                    if (!$N || $N[0] != "\\") {
                                        break;
                                    }
                                }
                            }
                        } else {
                            $ic = false;
                            $H = substr($I, 0, $Pf + ($Kb[0] == "\n" ? 3 : 0));
                            $eb++;
                            $Xf =
                                "<pre id='sql-$eb'><code class='jush-" .
                                JUSH .
                                "'>" .
                                adminer()->sqlCommandQuery($H) .
                                "</code></pre>\n";
                            if (
                                JUSH == "sqlite" &&
                                preg_match("~^$Wg*+ATTACH\\b~i", $H, $B)
                            ) {
                                echo $Xf,
                                    "<p class='error'>" .
                                        "ATTACH queries are not supported." .
                                        "\n";
                                $qc[] = " <a href='#sql-$eb'>$eb</a>";
                                if ($_POST["error_stops"]) {
                                    break;
                                }
                            } else {
                                if (!$_POST["only_errors"]) {
                                    echo $Xf;
                                    ob_flush();
                                    flush();
                                }
                                $ch = microtime(true);
                                if (
                                    connection()->multi_query($H) &&
                                    $f &&
                                    preg_match("~^$Wg*+USE\\b~i", $H)
                                ) {
                                    $f->query($H);
                                }
                                do {
                                    $J = connection()->store_result();
                                    if (connection()->error) {
                                        echo $_POST["only_errors"] ? $Xf : "",
                                            "<p class='error'>" .
                                                "Error in query" .
                                                (connection()->errno
                                                    ? " (" .
                                                        connection()->errno .
                                                        ")"
                                                    : "") .
                                                ": " .
                                                error() .
                                                "\n";
                                        $qc[] = " <a href='#sql-$eb'>$eb</a>";
                                        if ($_POST["error_stops"]) {
                                            break 2;
                                        }
                                    } else {
                                        $Ch =
                                            " <span class='time'>(" .
                                            format_time($ch) .
                                            ")</span>" .
                                            (strlen($H) < 1000
                                                ? " <a href='" .
                                                    h(ME) .
                                                    "sql=" .
                                                    urlencode(trim($H)) .
                                                    "'>" .
                                                    "Edit" .
                                                    "</a>"
                                                : "");
                                        $ja = connection()->affected_rows;
                                        $wi = $_POST["only_errors"]
                                            ? ""
                                            : driver()->warnings();
                                        $xi = "warnings-$eb";
                                        if ($wi) {
                                            $Ch .=
                                                ", <a href='#$xi'>" .
                                                "Warnings" .
                                                "</a>" .
                                                script(
                                                    "qsl('a').onclick = partial(toggle, '$xi');",
                                                    "",
                                                );
                                        }
                                        $yc = null;
                                        $qf = null;
                                        $zc = "explain-$eb";
                                        if (is_object($J)) {
                                            $z = $_POST["limit"];
                                            $qf = print_select_result(
                                                $J,
                                                $f,
                                                [],
                                                $z,
                                            );
                                            if (!$_POST["only_errors"]) {
                                                echo "<form action='' method='post'>\n";
                                                $Ue = $J->num_rows;
                                                echo "<p class='sql-footer'>" .
                                                ($Ue
                                                    ? ($z && $Ue > $z
                                                            ? sprintf(
                                                                "%d / ",
                                                                $z,
                                                            )
                                                            : "") .
                                                        lang_format(
                                                            [
                                                                "%d row",
                                                                "%d rows",
                                                            ],
                                                            $Ue,
                                                        )
                                                    : ""),
                                                    $Ch;
                                                if (
                                                    $f &&
                                                    preg_match(
                                                        "~^($Wg|\\()*+SELECT\\b~i",
                                                        $H,
                                                    ) &&
                                                    ($yc = explain($f, $H))
                                                ) {
                                                    echo ", <a href='#$zc'>Explain</a>" .
                                                        script(
                                                            "qsl('a').onclick = partial(toggle, '$zc');",
                                                            "",
                                                        );
                                                }
                                                $u = "export-$eb";
                                                echo ", <a href='#$u'>" .
                                                    "Export" .
                                                    "</a>" .
                                                    script(
                                                        "qsl('a').onclick = partial(toggle, '$u');",
                                                        "",
                                                    ) .
                                                    "<span id='$u' class='hidden'>: " .
                                                    html_select(
                                                        "output",
                                                        adminer()->dumpOutput(),
                                                        $ha["output"],
                                                    ) .
                                                    " " .
                                                    html_select(
                                                        "format",
                                                        $Zb,
                                                        $ha["format"],
                                                    ) .
                                                    input_hidden("query", $H) .
                                                    "<input type='submit' name='export' value='" .
                                                    "Export" .
                                                    "'>" .
                                                    input_token() .
                                                    "</span>\n" .
                                                    "</form>\n";
                                            }
                                        } else {
                                            if (
                                                preg_match(
                                                    "~^$Wg*+(CREATE|DROP|ALTER)$Wg++(DATABASE|SCHEMA)\\b~i",
                                                    $H,
                                                )
                                            ) {
                                                restart_session();
                                                set_session("dbs", null);
                                                stop_session();
                                            }
                                            if (!$_POST["only_errors"]) {
                                                echo "<p class='message' title='" .
                                                    h(connection()->info) .
                                                    "'>" .
                                                    lang_format(
                                                        [
                                                            "Query executed OK, %d row affected.",
                                                            "Query executed OK, %d rows affected.",
                                                        ],
                                                        $ja,
                                                    ) .
                                                    "$Ch\n";
                                            }
                                        }
                                        echo $wi
                                            ? "<div id='$xi' class='hidden'>\n$wi</div>\n"
                                            : "";
                                        if ($yc) {
                                            echo "<div id='$zc' class='hidden explain'>\n";
                                            print_select_result($yc, $f, $qf);
                                            echo "</div>\n";
                                        }
                                    }
                                    $ch = microtime(true);
                                } while (connection()->next_result());
                            }
                            $I = substr($I, $Xe);
                            $Xe = 0;
                        }
                    }
                }
            }
            if ($ic) {
                echo "<p class='message'>" . "No commands to execute." . "\n";
            } elseif ($_POST["only_errors"]) {
                echo "<p class='message'>" .
                lang_format(
                    ["%d query executed OK.", "%d queries executed OK."],
                    $eb - count($qc),
                ),
                    " <span class='time'>(" . format_time($Nh) . ")</span>\n";
            } elseif ($qc && $eb > 1) {
                echo "<p class='error'>" .
                    "Error in query" .
                    ": " .
                    implode("", $qc) .
                    "\n";
            }
        } else {
            echo "<p class='error'>" . upload_error($I) . "\n";
        }
    }
    echo '
<form action="" method="post" enctype="multipart/form-data" id="form">
';
    $wc = "<input type='submit' value='" . "Execute" . "' title='Ctrl+Enter'>";
    if (!isset($_GET["import"])) {
        $H = $_GET["sql"];
        if ($_POST) {
            $H = $_POST["query"];
        } elseif ($_GET["history"] == "all") {
            $H = $od;
        } elseif ($_GET["history"] != "") {
            $H = idx($od[$_GET["history"]], 0);
        }
        echo "<p>";
        textarea("query", $H, 20);
        echo script(
        ($_POST ? "" : "qs('textarea').focus();\n") .
            "qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '" .
            js_escape(
                remove_from_uri("sql|limit|error_stops|only_errors|history"),
            ) .
            "');",
    ),
            "<p>";
        adminer()->sqlPrintAfter();
        echo "$wc\n",
            "Limit rows" .
                ": <input type='number' name='limit' class='size' value='" .
                h($_POST ? $_POST["limit"] : $_GET["limit"]) .
                "'>\n";
    } else {
        echo "<fieldset><legend>" . "File upload" . "</legend><div>";
        $fd = extension_loaded("zlib") ? "[.gz]" : "";
        echo ini_bool("file_uploads")
        ? "SQL$fd (&lt; " .
            ini_get("upload_max_filesize") .
            "B): <input type='file' name='sql_file[]' multiple>\n$wc"
        : "File uploads are disabled.",
            "</div></fieldset>\n";
        $yd = adminer()->importServerPath();
        if ($yd) {
            echo "<fieldset><legend>" . "From server" . "</legend><div>",
                sprintf("Webserver file %s", "<code>" . h($yd) . "$fd</code>"),
                ' <input type="submit" name="webfile" value="' .
                    "Run file" .
                    '">',
                "</div></fieldset>\n";
        }
        echo "<p>";
    }
    echo checkbox(
    "error_stops",
    1,
    $_POST
        ? $_POST["error_stops"]
        : isset($_GET["import"]) || $_GET["error_stops"],
    "Stop on error",
) . "\n",
        checkbox(
            "only_errors",
            1,
            $_POST
                ? $_POST["only_errors"]
                : isset($_GET["import"]) || $_GET["only_errors"],
            "Show only errors",
        ) . "\n",
        input_token();
    if (!isset($_GET["import"]) && $od) {
        print_fieldset("history", "History", $_GET["history"] != "");
        for ($X = end($od); $X; $X = prev($od)) {
            $y = key($od);
            [$H, $Ch, $dc] = $X;
            echo '<a href="' .
                h(ME . "sql=&history=$y") .
                '">' .
                "Edit" .
                "</a>" .
                " <span class='time' title='" .
                @date("Y-m-d", $Ch) .
                "'>" .
                @date("H:i:s", $Ch) .
                "</span>" .
                " <code class='jush-" .
                JUSH .
                "'>" .
                shorten_utf8(
                    ltrim(
                        str_replace(
                            "\n",
                            " ",
                            str_replace(
                                "\r",
                                "",
                                preg_replace("~^(#|$je).*~m", "", $H),
                            ),
                        ),
                    ),
                    80,
                    "</code>",
                ) .
                ($dc ? " <span class='time'>($dc)</span>" : "") .
                "<br>\n";
        }
        echo "<input type='submit' name='clear' value='" . "Clear" . "'>\n",
            "<a href='" .
                h(ME . "sql=&history=all") .
                "'>" .
                "Edit all" .
                "</a>\n",
            "</div></fieldset>\n";
    }
    echo '</form>
';
} elseif (isset($_GET["edit"])) {
    $a = $_GET["edit"];
    $m = fields($a);
    $Z = isset($_GET["select"])
        ? ($_POST["check"] && count($_POST["check"]) == 1
            ? where_check($_POST["check"][0], $m)
            : "")
        : where($_GET, $m);
    $fi = isset($_GET["select"]) ? $_POST["edit"] : $Z;
    foreach ($m as $D => $l) {
        if (
            !isset($l["privileges"][$fi ? "update" : "insert"]) ||
            adminer()->fieldName($l) == "" ||
            $l["generated"]
        ) {
            unset($m[$D]);
        }
    }
    if ($_POST && !$k && !isset($_GET["select"])) {
        $A = $_POST["referer"];
        if ($_POST["insert"]) {
            $A = $fi ? null : $_SERVER["REQUEST_URI"];
        } elseif (!preg_match('~^.+&select=.+$~', $A)) {
            $A = ME . "select=" . urlencode($a);
        }
        $x = indexes($a);
        $ai = unique_array($_GET["where"], $x);
        $gg = "\nWHERE $Z";
        if (isset($_POST["delete"])) {
            queries_redirect(
                $A,
                "Item has been deleted.",
                driver()->delete($a, $gg, $ai ? 0 : 1),
            );
        } else {
            $Q = [];
            foreach ($m as $D => $l) {
                $X = process_input($l);
                if ($X !== false && $X !== null) {
                    $Q[idf_escape($D)] = $X;
                }
            }
            if ($fi) {
                if (!$Q) {
                    redirect($A);
                }
                queries_redirect(
                    $A,
                    "Item has been updated.",
                    driver()->update($a, $Q, $gg, $ai ? 0 : 1),
                );
                if (is_ajax()) {
                    page_headers();
                    page_messages($k);
                    exit();
                }
            } else {
                $J = driver()->insert($a, $Q);
                $be = $J ? last_id($J) : 0;
                queries_redirect(
                    $A,
                    sprintf("Item%s has been inserted.", $be ? " $be" : ""),
                    $J,
                );
            }
        }
    }
    $L = null;
    if ($_POST["save"]) {
        $L = (array) $_POST["fields"];
    } elseif ($Z) {
        $O = [];
        foreach ($m as $D => $l) {
            if (isset($l["privileges"]["select"])) {
                $sa =
                    $_POST["clone"] && $l["auto_increment"]
                        ? "''"
                        : convert_field($l);
                $O[] = ($sa ? "$sa AS " : "") . idf_escape($D);
            }
        }
        $L = [];
        if (!support("table")) {
            $O = ["*"];
        }
        if ($O) {
            $J = driver()->select(
                $a,
                $O,
                [$Z],
                $O,
                [],
                isset($_GET["select"]) ? 2 : 1,
            );
            if (!$J) {
                $k = error();
            } else {
                $L = $J->fetch_assoc();
                if (!$L) {
                    $L = false;
                }
            }
            if (isset($_GET["select"]) && (!$L || $J->fetch_assoc())) {
                $L = null;
            }
        }
    }
    if (!support("table") && !$m) {
        if (!$Z) {
            $J = driver()->select($a, ["*"], [], ["*"]);
            $L = $J ? $J->fetch_assoc() : false;
            if (!$L) {
                $L = [driver()->primary => ""];
            }
        }
        if ($L) {
            foreach ($L as $y => $X) {
                if (!$Z) {
                    $L[$y] = null;
                }
                $m[$y] = [
                    "field" => $y,
                    "null" => $y != driver()->primary,
                    "auto_increment" => $y == driver()->primary,
                ];
            }
        }
    }
    edit_form($a, $m, $L, $fi, $k);
} elseif (isset($_GET["create"])) {
    $a = $_GET["create"];
    $Cf = [];
    foreach (
        ["HASH", "LINEAR HASH", "KEY", "LINEAR KEY", "RANGE", "LIST"]
        as $y
    ) {
        $Cf[$y] = $y;
    }
    $mg = referencable_primary($a);
    $p = [];
    foreach ($mg as $ph => $l) {
        $p[
            str_replace("`", "``", $ph) .
                "`" .
                str_replace("`", "``", $l["field"])
        ] = $ph;
    }
    $tf = [];
    $S = [];
    if ($a != "") {
        $tf = fields($a);
        $S = table_status1($a);
        if (count($S) < 2) {
            $k = "No tables.";
        }
    }
    $L = $_POST;
    $L["fields"] = (array) $L["fields"];
    if ($L["auto_increment_col"]) {
        $L["fields"][$L["auto_increment_col"]]["auto_increment"] = true;
    }
    if ($_POST) {
        save_settings([
            "comments" => $_POST["comments"],
            "defaults" => $_POST["defaults"],
        ]);
    }
    if ($_POST && !process_fields($L["fields"]) && !$k) {
        if ($_POST["drop"]) {
            queries_redirect(
                substr(ME, 0, -1),
                "Table has been dropped.",
                drop_tables([$a]),
            );
        } else {
            $m = [];
            $na = [];
            $ji = false;
            $Qc = [];
            $sf = reset($tf);
            $la = " FIRST";
            foreach ($L["fields"] as $y => $l) {
                $o = $p[$l["type"]];
                $Vh = $o !== null ? $mg[$o] : $l;
                if ($l["field"] != "") {
                    if (!$l["generated"]) {
                        $l["default"] = null;
                    }
                    $cg = process_field($l, $Vh);
                    $na[] = [$l["orig"], $cg, $la];
                    if (!$sf || $cg !== process_field($sf, $sf)) {
                        $m[] = [$l["orig"], $cg, $la];
                        if ($l["orig"] != "" || $la) {
                            $ji = true;
                        }
                    }
                    if ($o !== null) {
                        $Qc[idf_escape($l["field"])] =
                            ($a != "" && JUSH != "sqlite" ? "ADD" : " ") .
                            format_foreign_key([
                                "table" => $p[$l["type"]],
                                "source" => [$l["field"]],
                                "target" => [$Vh["field"]],
                                "on_delete" => $l["on_delete"],
                            ]);
                    }
                    $la = " AFTER " . idf_escape($l["field"]);
                } elseif ($l["orig"] != "") {
                    $ji = true;
                    $m[] = [$l["orig"]];
                }
                if ($l["orig"] != "") {
                    $sf = next($tf);
                    if (!$sf) {
                        $la = "";
                    }
                }
            }
            $Ef = "";
            if (support("partitioning")) {
                if (isset($Cf[$L["partition_by"]])) {
                    $F = [];
                    foreach ($L as $y => $X) {
                        if (preg_match("~^partition~", $y)) {
                            $F[$y] = $X;
                        }
                    }
                    foreach ($F["partition_names"] as $y => $D) {
                        if ($D == "") {
                            unset($F["partition_names"][$y]);
                            unset($F["partition_values"][$y]);
                        }
                    }
                    if ($F != get_partitions_info($a)) {
                        $Ff = [];
                        if (
                            $F["partition_by"] == "RANGE" ||
                            $F["partition_by"] == "LIST"
                        ) {
                            foreach ($F["partition_names"] as $y => $D) {
                                $Y = $F["partition_values"][$y];
                                $Ff[] =
                                    "\n  PARTITION " .
                                    idf_escape($D) .
                                    " VALUES " .
                                    ($F["partition_by"] == "RANGE"
                                        ? "LESS THAN"
                                        : "IN") .
                                    ($Y != "" ? " ($Y)" : " MAXVALUE");
                            }
                        }
                        $Ef .= "\nPARTITION BY $F[partition_by]($F[partition])";
                        if ($Ff) {
                            $Ef .= " (" . implode(",", $Ff) . "\n)";
                        } elseif ($F["partitions"]) {
                            $Ef .= " PARTITIONS " . +$F["partitions"];
                        }
                    }
                } elseif (preg_match("~partitioned~", $S["Create_options"])) {
                    $Ef .= "\nREMOVE PARTITIONING";
                }
            }
            $C = "Table has been altered.";
            if ($a == "") {
                cookie("adminer_engine", $L["Engine"]);
                $C = "Table has been created.";
            }
            $D = trim($L["name"]);
            queries_redirect(
                ME . (support("table") ? "table=" : "select=") . urlencode($D),
                $C,
                alter_table(
                    $a,
                    $D,
                    JUSH == "sqlite" && ($ji || $Qc) ? $na : $m,
                    $Qc,
                    $L["Comment"] != $S["Comment"] ? $L["Comment"] : null,
                    $L["Engine"] && $L["Engine"] != $S["Engine"]
                        ? $L["Engine"]
                        : "",
                    $L["Collation"] && $L["Collation"] != $S["Collation"]
                        ? $L["Collation"]
                        : "",
                    $L["Auto_increment"] != ""
                        ? number($L["Auto_increment"])
                        : "",
                    $Ef,
                ),
            );
        }
    }
    page_header(
        $a != "" ? "Alter table" : "Create table",
        $k,
        ["table" => $a],
        h($a),
    );
    if (!$_POST) {
        $Xh = driver()->types();
        $L = [
            "Engine" => $_COOKIE["adminer_engine"],
            "fields" => [
                [
                    "field" => "",
                    "type" => isset($Xh["int"])
                        ? "int"
                        : (isset($Xh["integer"])
                            ? "integer"
                            : ""),
                    "on_update" => "",
                ],
            ],
            "partition_names" => [""],
        ];
        if ($a != "") {
            $L = $S;
            $L["name"] = $a;
            $L["fields"] = [];
            if (!$_GET["auto_increment"]) {
                $L["Auto_increment"] = "";
            }
            foreach ($tf as $l) {
                $l["generated"] =
                    $l["generated"] ?: (isset($l["default"]) ? "DEFAULT" : "");
                $L["fields"][] = $l;
            }
            if (support("partitioning")) {
                $L += get_partitions_info($a);
                $L["partition_names"][] = "";
                $L["partition_values"][] = "";
            }
        }
    }
    $b = collations();
    if (is_array(reset($b))) {
        $b = call_user_func_array("array_merge", array_values($b));
    }
    $kc = driver()->engines();
    foreach ($kc as $jc) {
        if (!strcasecmp($jc, $L["Engine"])) {
            $L["Engine"] = $jc;
            break;
        }
    }
    echo '
<form action="" method="post" id="form">
<p>
';
    if (support("columns") || $a == "") {
        echo "Table name" .
        ": <input name='name'" .
        ($a == "" && !$_POST ? " autofocus" : "") .
        " data-maxlength='64' value='" .
        h($L["name"]) .
        "' autocapitalize='off'>\n",
            $kc
                ? html_select(
                        "Engine",
                        ["" => "(" . "engine" . ")"] + $kc,
                        $L["Engine"],
                    ) .
                    on_help("event.target.value", 1) .
                    script("qsl('select').onchange = helpClose;") .
                    "\n"
                : "";
        if ($b) {
            echo "<datalist id='collations'>" .
            optionlist($b) .
            "</datalist>\n",
                preg_match("~sqlite|mssql~", JUSH)
                    ? ""
                    : "<input list='collations' name='Collation' value='" .
                        h($L["Collation"]) .
                        "' placeholder='(" .
                        "collation" .
                        ")'>\n";
        }
        echo "<input type='submit' value='" . "Save" . "'>\n";
    }
    if (support("columns")) {
        echo "<div class='scrollable'>\n",
            "<table id='edit-fields' class='nowrap'>\n";
        edit_fields($L["fields"], $b, "TABLE", $p);
        echo "</table>\n",
            script("editFields();"),
            "</div>\n<p>\n",
            "Auto Increment" .
                ": <input type='number' name='Auto_increment' class='size' value='" .
                h($L["Auto_increment"]) .
                "'>\n",
            checkbox(
                "defaults",
                1,
                $_POST ? $_POST["defaults"] : get_setting("defaults"),
                "Default values",
                "columnShow(this.checked, 5)",
                "jsonly",
            );
        $hb = $_POST ? $_POST["comments"] : get_setting("comments");
        echo support("comment")
        ? checkbox(
                "comments",
                1,
                $hb,
                "Comment",
                "editingCommentsClick(this, true);",
                "jsonly",
            ) .
            " " .
            (preg_match('~\n~', $L["Comment"])
                ? "<textarea name='Comment' rows='2' cols='20'" .
                    ($hb ? "" : " class='hidden'") .
                    ">" .
                    h($L["Comment"]) .
                    "</textarea>"
                : '<input name="Comment" value="' .
                    h($L["Comment"]) .
                    '" data-maxlength="' .
                    (min_version(5.5) ? 2048 : 60) .
                    '"' .
                    ($hb ? "" : " class='hidden'") .
                    ">")
        : "",
            '<p>
<input type="submit" value="Save">
';
    }
    echo '
';
    if ($a != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $a));
    }
    if (support("partitioning")) {
        $Df = preg_match("~RANGE|LIST~", $L["partition_by"]);
        print_fieldset("partition", "Partition by", $L["partition_by"]);
        echo "<p>" .
        html_select("partition_by", ["" => ""] + $Cf, $L["partition_by"]) .
        on_help("event.target.value.replace(/./, 'PARTITION BY \$&')", 1) .
        script("qsl('select').onchange = partitionByChange;"),
            "(<input name='partition' value='" . h($L["partition"]) . "'>)\n",
            "Partitions" .
                ": <input type='number' name='partitions' class='size" .
                ($Df || !$L["partition_by"] ? " hidden" : "") .
                "' value='" .
                h($L["partitions"]) .
                "'>\n",
            "<table id='partition-table'" .
                ($Df ? "" : " class='hidden'") .
                ">\n",
            "<thead><tr><th>" .
                "Partition name" .
                "<th>" .
                "Values" .
                "</thead>\n";
        foreach ($L["partition_names"] as $y => $X) {
            echo "<tr>",
                '<td><input name="partition_names[]" value="' .
                    h($X) .
                    '" autocapitalize="off">',
                $y == count($L["partition_names"]) - 1
                    ? script("qsl('input').oninput = partitionNameChange;")
                    : "",
                '<td><input name="partition_values[]" value="' .
                    h(idx($L["partition_values"], $y)) .
                    '">';
        }
        echo "</table>\n</div></fieldset>\n";
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["indexes"])) {
    $a = $_GET["indexes"];
    $Bd = ["PRIMARY", "UNIQUE", "INDEX"];
    $S = table_status1($a, true);
    if (
        preg_match(
            "~MyISAM|M?aria" .
                (min_version(5.6, "10.0.5") ? "|InnoDB" : "") .
                "~i",
            $S["Engine"],
        )
    ) {
        $Bd[] = "FULLTEXT";
    }
    if (
        preg_match(
            "~MyISAM|M?aria" .
                (min_version(5.7, "10.2.2") ? "|InnoDB" : "") .
                "~i",
            $S["Engine"],
        )
    ) {
        $Bd[] = "SPATIAL";
    }
    $x = indexes($a);
    $Wf = [];
    if (JUSH == "mongo") {
        $Wf = $x["_id_"];
        unset($Bd[0]);
        unset($x["_id_"]);
    }
    $L = $_POST;
    if ($L) {
        save_settings(["index_options" => $L["options"]]);
    }
    if ($_POST && !$k && !$_POST["add"] && !$_POST["drop_col"]) {
        $oa = [];
        foreach ($L["indexes"] as $w) {
            $D = $w["name"];
            if (in_array($w["type"], $Bd)) {
                $d = [];
                $he = [];
                $Nb = [];
                $Q = [];
                ksort($w["columns"]);
                foreach ($w["columns"] as $y => $c) {
                    if ($c != "") {
                        $ge = idx($w["lengths"], $y);
                        $Lb = idx($w["descs"], $y);
                        $Q[] =
                            idf_escape($c) .
                            ($ge ? "(" . +$ge . ")" : "") .
                            ($Lb ? " DESC" : "");
                        $d[] = $c;
                        $he[] = $ge ?: null;
                        $Nb[] = $Lb;
                    }
                }
                $xc = $x[$D];
                if ($xc) {
                    ksort($xc["columns"]);
                    ksort($xc["lengths"]);
                    ksort($xc["descs"]);
                    if (
                        $w["type"] == $xc["type"] &&
                        array_values($xc["columns"]) === $d &&
                        (!$xc["lengths"] ||
                            array_values($xc["lengths"]) === $he) &&
                        array_values($xc["descs"]) === $Nb
                    ) {
                        unset($x[$D]);
                        continue;
                    }
                }
                if ($d) {
                    $oa[] = [$w["type"], $D, $Q];
                }
            }
        }
        foreach ($x as $D => $xc) {
            $oa[] = [$xc["type"], $D, "DROP"];
        }
        if (!$oa) {
            redirect(ME . "table=" . urlencode($a));
        }
        queries_redirect(
            ME . "table=" . urlencode($a),
            "Indexes have been altered.",
            alter_indexes($a, $oa),
        );
    }
    page_header("Indexes", $k, ["table" => $a], h($a));
    $m = array_keys(fields($a));
    if ($_POST["add"]) {
        foreach ($L["indexes"] as $y => $w) {
            if ($w["columns"][count($w["columns"])] != "") {
                $L["indexes"][$y]["columns"][] = "";
            }
        }
        $w = end($L["indexes"]);
        if ($w["type"] || array_filter($w["columns"], "strlen")) {
            $L["indexes"][] = ["columns" => [1 => ""]];
        }
    }
    if (!$L) {
        foreach ($x as $y => $w) {
            $x[$y]["name"] = $y;
            $x[$y]["columns"][] = "";
        }
        $x[] = ["columns" => [1 => ""]];
        $L["indexes"] = $x;
    }
    $he = JUSH == "sql" || JUSH == "mssql";
    $Qg = $_POST ? $_POST["options"] : get_setting("index_options");
    echo '
<form action="" method="post">
<div class="scrollable">
<table class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">',
        "Columns" .
            ($he
                ? "<span class='idxopts" .
                    ($Qg ? "" : " hidden") .
                    "'> (" .
                    "length" .
                    ")</span>"
                : "");
    if ($he || support("descidx")) {
        echo checkbox(
            "options",
            1,
            $Qg,
            "Options",
            "indexOptionsShow(this.checked)",
            "jsonly",
        ) . "\n";
    }
    echo '<th id="label-name">Name
<th><noscript>',
        icon("plus", "add[0]", "+", "Add next"),
        '</noscript>
</thead>
';
    if ($Wf) {
        echo "<tr><td>PRIMARY<td>";
        foreach ($Wf["columns"] as $y => $c) {
            echo select_input(" disabled", $m, $c),
                "<label><input disabled type='checkbox'>" .
                    "descending" .
                    "</label> ";
        }
        echo "<td><td>\n";
    }
    $Rd = 1;
    foreach ($L["indexes"] as $w) {
        if (!$_POST["drop_col"] || $Rd != key($_POST["drop_col"])) {
            echo "<tr><td>" .
            html_select(
                "indexes[$Rd][type]",
                [-1 => ""] + $Bd,
                $w["type"],
                $Rd == count($L["indexes"]) ? "indexesAddRow.call(this);" : "",
                "label-type",
            ),
                "<td>";
            ksort($w["columns"]);
            $t = 1;
            foreach ($w["columns"] as $y => $c) {
                echo "<span>" .
                select_input(
                    " name='indexes[$Rd][columns][$t]' title='" .
                        "Column" .
                        "'",
                    $m ? array_combine($m, $m) : $m,
                    $c,
                    "partial(" .
                        ($t == count($w["columns"])
                            ? "indexesAddColumn"
                            : "indexesChangeColumn") .
                        ", '" .
                        js_escape(JUSH == "sql" ? "" : $_GET["indexes"] . "_") .
                        "')",
                ),
                    "<span class='idxopts" . ($Qg ? "" : " hidden") . "'>",
                    $he
                        ? "<input type='number' name='indexes[$Rd][lengths][$t]' class='size' value='" .
                            h(idx($w["lengths"], $y)) .
                            "' title='" .
                            "Length" .
                            "'>"
                        : "",
                    support("descidx")
                        ? checkbox(
                            "indexes[$Rd][descs][$t]",
                            1,
                            idx($w["descs"], $y),
                            "descending",
                        )
                        : "",
                    "</span> </span>";
                $t++;
            }
            echo "<td><input name='indexes[$Rd][name]' value='" .
            h($w["name"]) .
            "' autocapitalize='off' aria-labelledby='label-name'>\n",
                "<td>" .
                    icon("cross", "drop_col[$Rd]", "x", "Remove") .
                    script(
                        "qsl('button').onclick = partial(editingRemoveRow, 'indexes\$1[type]');",
                    );
        }
        $Rd++;
    }
    echo '</table>
</div>
<p>
<input type="submit" value="Save">
',
        input_token(),
        '</form>
';
} elseif (isset($_GET["database"])) {
    $L = $_POST;
    if ($_POST && !$k && !$_POST["add"]) {
        $D = trim($L["name"]);
        if ($_POST["drop"]) {
            $_GET["db"] = "";
            queries_redirect(
                remove_from_uri("db|database"),
                "Database has been dropped.",
                drop_databases([DB]),
            );
        } elseif (DB !== $D) {
            if (DB != "") {
                $_GET["db"] = $D;
                queries_redirect(
                    preg_replace("~\bdb=[^&]*&~", "", ME) .
                        "db=" .
                        urlencode($D),
                    "Database has been renamed.",
                    rename_database($D, $L["collation"]),
                );
            } else {
                $h = explode("\n", str_replace("\r", "", $D));
                $jh = true;
                $ae = "";
                foreach ($h as $i) {
                    if (count($h) == 1 || $i != "") {
                        if (!create_database($i, $L["collation"])) {
                            $jh = false;
                        }
                        $ae = $i;
                    }
                }
                restart_session();
                set_session("dbs", null);
                queries_redirect(
                    ME . "db=" . urlencode($ae),
                    "Database has been created.",
                    $jh,
                );
            }
        } else {
            if (!$L["collation"]) {
                redirect(substr(ME, 0, -1));
            }
            query_redirect(
                "ALTER DATABASE " .
                    idf_escape($D) .
                    (preg_match('~^[a-z0-9_]+$~i', $L["collation"])
                        ? " COLLATE $L[collation]"
                        : ""),
                substr(ME, 0, -1),
                "Database has been altered.",
            );
        }
    }
    page_header(DB != "" ? "Alter database" : "Create database", $k, [], h(DB));
    $b = collations();
    $D = DB;
    if ($_POST) {
        $D = $L["name"];
    } elseif (DB != "") {
        $L["collation"] = db_collation(DB, $b);
    } elseif (JUSH == "sql") {
        foreach (get_vals("SHOW GRANTS") as $ad) {
            if (
                preg_match("~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~", $ad, $B) &&
                $B[1]
            ) {
                $D = stripcslashes(idf_unescape("`$B[2]`"));
                break;
            }
        }
    }
    echo '
<form action="" method="post">
<p>
',
        ($_POST["add"] || strpos($D, "\n")
            ? '<textarea autofocus name="name" rows="10" cols="40">' .
                h($D) .
                "</textarea><br>"
            : '<input name="name" autofocus value="' .
                h($D) .
                '" data-maxlength="64" autocapitalize="off">') .
            "\n" .
            ($b
                ? html_select(
                        "collation",
                        ["" => "(" . "collation" . ")"] + $b,
                        $L["collation"],
                    ) .
                    doc_link([
                        "sql" => "charset-charsets.html",
                        "mariadb" => "supported-character-sets-and-collations/",
                    ])
                : ""),
        '<input type="submit" value="Save">
';
    if (DB != "") {
        echo "<input type='submit' name='drop' value='" .
            "Drop" .
            "'>" .
            confirm(sprintf("Drop %s?", DB)) .
            "\n";
    } elseif (!$_POST["add"] && $_GET["db"] == "") {
        echo icon("plus", "add[0]", "+", "Add next") . "\n";
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["call"])) {
    $ba = $_GET["name"] ?: $_GET["call"];
    page_header("Call" . ": " . h($ba), $k);
    $yg = routine(
        $_GET["call"],
        isset($_GET["callf"]) ? "FUNCTION" : "PROCEDURE",
    );
    $zd = [];
    $xf = [];
    foreach ($yg["fields"] as $t => $l) {
        if (substr($l["inout"], -3) == "OUT") {
            $xf[$t] =
                "@" .
                idf_escape($l["field"]) .
                " AS " .
                idf_escape($l["field"]);
        }
        if (!$l["inout"] || substr($l["inout"], 0, 2) == "IN") {
            $zd[] = $t;
        }
    }
    if (!$k && $_POST) {
        $Ma = [];
        foreach ($yg["fields"] as $y => $l) {
            $X = "";
            if (in_array($y, $zd)) {
                $X = process_input($l);
                if ($X === false) {
                    $X = "''";
                }
                if (isset($xf[$y])) {
                    connection()->query(
                        "SET @" . idf_escape($l["field"]) . " = $X",
                    );
                }
            }
            $Ma[] = isset($xf[$y]) ? "@" . idf_escape($l["field"]) : $X;
        }
        $I =
            (isset($_GET["callf"]) ? "SELECT" : "CALL") .
            " " .
            table($ba) .
            "(" .
            implode(", ", $Ma) .
            ")";
        $ch = microtime(true);
        $J = connection()->multi_query($I);
        $ja = connection()->affected_rows;
        echo adminer()->selectQuery($I, $ch, !$J);
        if (!$J) {
            echo "<p class='error'>" . error() . "\n";
        } else {
            $f = connect();
            if ($f) {
                $f->select_db(DB);
            }
            do {
                $J = connection()->store_result();
                if (is_object($J)) {
                    print_select_result($J, $f);
                } else {
                    echo "<p class='message'>" .
                        lang_format(
                            [
                                "Routine has been called, %d row affected.",
                                "Routine has been called, %d rows affected.",
                            ],
                            $ja,
                        ) .
                        " <span class='time'>" .
                        @date("H:i:s") .
                        "</span>\n";
                }
            } while (connection()->next_result());
            if ($xf) {
                print_select_result(
                    connection()->query("SELECT " . implode(", ", $xf)),
                );
            }
        }
    }
    echo '
<form action="" method="post">
';
    if ($zd) {
        echo "<table class='layout'>\n";
        foreach ($zd as $y) {
            $l = $yg["fields"][$y];
            $D = $l["field"];
            echo "<tr><th>" . adminer()->fieldName($l);
            $Y = idx($_POST["fields"], $D);
            if ($Y != "") {
                if ($l["type"] == "set") {
                    $Y = implode(",", $Y);
                }
            }
            input($l, $Y, idx($_POST["function"], $D, ""));
            echo "\n";
        }
        echo "</table>\n";
    }
    echo '<p>
<input type="submit" value="Call">
',
        input_token(),
        '</form>

<pre>
';
    function pre_tr($N)
    {
        return preg_replace(
            "~^~m",
            "<tr>",
            preg_replace("~\|~", "<td>", preg_replace('~\|$~m', "", rtrim($N))),
        );
    }
    $R = '(\+--[-+]+\+\n)';
    $L = '(\| .* \|\n)';
    echo preg_replace_callback(
        "~^$R?$L$R?($L*)$R?~m",
        function ($B) {
            $Mc = pre_tr($B[2]);
            return "<table>\n" .
                ($B[1] ? "<thead>$Mc</thead>\n" : $Mc) .
                pre_tr($B[4]) .
                "\n</table>";
        },
        preg_replace(
            '~(\n(    -|mysql)&gt; )(.+)~',
            "\\1<code class='jush-sql'>\\3</code>",
            preg_replace('~(.+)\n---+\n~', "<b>\\1</b>\n", h($yg["comment"])),
        ),
    );
    echo '</pre>
';
} elseif (isset($_GET["foreign"])) {
    $a = $_GET["foreign"];
    $D = $_GET["name"];
    $L = $_POST;
    if (
        $_POST &&
        !$k &&
        !$_POST["add"] &&
        !$_POST["change"] &&
        !$_POST["change-js"]
    ) {
        if (!$_POST["drop"]) {
            $L["source"] = array_filter($L["source"], "strlen");
            ksort($L["source"]);
            $xh = [];
            foreach ($L["source"] as $y => $X) {
                $xh[$y] = $L["target"][$y];
            }
            $L["target"] = $xh;
        }
        if (JUSH == "sqlite") {
            $J = recreate_table(
                $a,
                $a,
                [],
                [],
                [" $D" => $L["drop"] ? "" : " " . format_foreign_key($L)],
            );
        } else {
            $oa = "ALTER TABLE " . table($a);
            $J =
                $D == "" ||
                queries(
                    "$oa DROP " .
                        (JUSH == "sql" ? "FOREIGN KEY " : "CONSTRAINT ") .
                        idf_escape($D),
                );
            if (!$L["drop"]) {
                $J = queries("$oa ADD" . format_foreign_key($L));
            }
        }
        queries_redirect(
            ME . "table=" . urlencode($a),
            $L["drop"]
                ? "Foreign key has been dropped."
                : ($D != ""
                    ? "Foreign key has been altered."
                    : "Foreign key has been created."),
            $J,
        );
        if (!$L["drop"]) {
            $k =
                "Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.";
        }
    }
    page_header("Foreign key", $k, ["table" => $a], h($a));
    if ($_POST) {
        ksort($L["source"]);
        if ($_POST["add"]) {
            $L["source"][] = "";
        } elseif ($_POST["change"] || $_POST["change-js"]) {
            $L["target"] = [];
        }
    } elseif ($D != "") {
        $p = foreign_keys($a);
        $L = $p[$D];
        $L["source"][] = "";
    } else {
        $L["table"] = $a;
        $L["source"] = [""];
    }
    echo '
<form action="" method="post">
';
    $Vg = array_keys(fields($a));
    if ($L["db"] != "") {
        connection()->select_db($L["db"]);
    }
    if ($L["ns"] != "") {
        $uf = get_schema();
        set_schema($L["ns"]);
    }
    $lg = array_keys(
        array_filter(table_status("", true), 'Adminer\fk_support'),
    );
    $xh = array_keys(
        fields(in_array($L["table"], $lg) ? $L["table"] : reset($lg)),
    );
    $ff = "this.form['change-js'].value = '1'; this.form.submit();";
    echo "<p><label>" .
        "Target table" .
        ": " .
        html_select("table", $lg, $L["table"], $ff) .
        "</label>\n";
    if (JUSH != "sqlite") {
        $Eb = [];
        foreach (adminer()->databases() as $i) {
            if (!information_schema($i)) {
                $Eb[] = $i;
            }
        }
        echo "<label>" .
            "DB" .
            ": " .
            html_select(
                "db",
                $Eb,
                $L["db"] != "" ? $L["db"] : $_GET["db"],
                $ff,
            ) .
            "</label>";
    }
    echo input_hidden("change-js"),
        '<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table>
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';
    $Rd = 0;
    foreach ($L["source"] as $y => $X) {
        echo "<tr>",
            "<td>" .
                html_select(
                    "source[" . +$y . "]",
                    [-1 => ""] + $Vg,
                    $X,
                    $Rd == count($L["source"]) - 1
                        ? "foreignAddRow.call(this);"
                        : "",
                    "label-source",
                ),
            "<td>" .
                html_select(
                    "target[" . +$y . "]",
                    $xh,
                    idx($L["target"], $y),
                    "",
                    "label-target",
                );
        $Rd++;
    }
    echo '</table>
<p>
<label>ON DELETE: ',
        html_select(
            "on_delete",
            [-1 => ""] + explode("|", driver()->onActions),
            $L["on_delete"],
        ),
        '</label>
<label>ON UPDATE: ',
        html_select(
            "on_update",
            [-1 => ""] + explode("|", driver()->onActions),
            $L["on_update"],
        ),
        '</label>
',
        doc_link([
            "sql" => "innodb-foreign-key-constraints.html",
            "mariadb" => "foreign-keys/",
        ]),
        '<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';
    if ($D != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $D));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["view"])) {
    $a = $_GET["view"];
    $L = $_POST;
    $vf = "VIEW";
    if (JUSH == "pgsql" && $a != "") {
        $dh = table_status1($a);
        $vf = strtoupper($dh["Engine"]);
    }
    if ($_POST && !$k) {
        $D = trim($L["name"]);
        $sa = " AS\n$L[select]";
        $A = ME . "table=" . urlencode($D);
        $C = "View has been altered.";
        $U = $_POST["materialized"] ? "MATERIALIZED VIEW" : "VIEW";
        if (
            !$_POST["drop"] &&
            $a == $D &&
            JUSH != "sqlite" &&
            $U == "VIEW" &&
            $vf == "VIEW"
        ) {
            query_redirect(
                (JUSH == "mssql" ? "ALTER" : "CREATE OR REPLACE") .
                    " VIEW " .
                    table($D) .
                    $sa,
                $A,
                $C,
            );
        } else {
            $zh = $D . "_adminer_" . uniqid();
            drop_create(
                "DROP $vf " . table($a),
                "CREATE $U " . table($D) . $sa,
                "DROP $U " . table($D),
                "CREATE $U " . table($zh) . $sa,
                "DROP $U " . table($zh),
                $_POST["drop"] ? substr(ME, 0, -1) : $A,
                "View has been dropped.",
                $C,
                "View has been created.",
                $a,
                $D,
            );
        }
    }
    if (!$_POST && $a != "") {
        $L = view($a);
        $L["name"] = $a;
        $L["materialized"] = $vf != "VIEW";
        if (!$k) {
            $k = error();
        }
    }
    page_header(
        $a != "" ? "Alter view" : "Create view",
        $k,
        ["table" => $a],
        h($a),
    );
    echo '
<form action="" method="post">
<p>Name: <input name="name" value="',
        h($L["name"]),
        '" data-maxlength="64" autocapitalize="off">
',
        support("materializedview")
            ? " " .
                checkbox(
                    "materialized",
                    1,
                    $L["materialized"],
                    "Materialized view",
                )
            : "",
        "<p>";
    textarea("select", $L["select"]);
    echo '<p>
<input type="submit" value="Save">
';
    if ($a != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $a));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["event"])) {
    $aa = $_GET["event"];
    $Jd = [
        "YEAR",
        "QUARTER",
        "MONTH",
        "DAY",
        "HOUR",
        "MINUTE",
        "WEEK",
        "SECOND",
        "YEAR_MONTH",
        "DAY_HOUR",
        "DAY_MINUTE",
        "DAY_SECOND",
        "HOUR_MINUTE",
        "HOUR_SECOND",
        "MINUTE_SECOND",
    ];
    $eh = [
        "ENABLED" => "ENABLE",
        "DISABLED" => "DISABLE",
        "SLAVESIDE_DISABLED" => "DISABLE ON SLAVE",
    ];
    $L = $_POST;
    if ($_POST && !$k) {
        if ($_POST["drop"]) {
            query_redirect(
                "DROP EVENT " . idf_escape($aa),
                substr(ME, 0, -1),
                "Event has been dropped.",
            );
        } elseif (
            in_array($L["INTERVAL_FIELD"], $Jd) &&
            isset($eh[$L["STATUS"]])
        ) {
            $Ag =
                "\nON SCHEDULE " .
                ($L["INTERVAL_VALUE"]
                    ? "EVERY " .
                        q($L["INTERVAL_VALUE"]) .
                        " $L[INTERVAL_FIELD]" .
                        ($L["STARTS"] ? " STARTS " . q($L["STARTS"]) : "") .
                        ($L["ENDS"] ? " ENDS " . q($L["ENDS"]) : "")
                    : "AT " . q($L["STARTS"])) .
                " ON COMPLETION" .
                ($L["ON_COMPLETION"] ? "" : " NOT") .
                " PRESERVE";
            queries_redirect(
                substr(ME, 0, -1),
                $aa != ""
                    ? "Event has been altered."
                    : "Event has been created.",
                queries(
                    ($aa != ""
                        ? "ALTER EVENT " .
                            idf_escape($aa) .
                            $Ag .
                            ($aa != $L["EVENT_NAME"]
                                ? "\nRENAME TO " . idf_escape($L["EVENT_NAME"])
                                : "")
                        : "CREATE EVENT " .
                            idf_escape($L["EVENT_NAME"]) .
                            $Ag) .
                        "\n" .
                        $eh[$L["STATUS"]] .
                        " COMMENT " .
                        q($L["EVENT_COMMENT"]) .
                        rtrim(" DO\n$L[EVENT_DEFINITION]", ";") .
                        ";",
                ),
            );
        }
    }
    page_header($aa != "" ? "Alter event" . ": " . h($aa) : "Create event", $k);
    if (!$L && $aa != "") {
        $M = get_rows(
            "SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = " .
                q(DB) .
                " AND EVENT_NAME = " .
                q($aa),
        );
        $L = reset($M);
    }
    echo '
<form action="" method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',
        h($L["EVENT_NAME"]),
        '" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',
        h("$L[EXECUTE_AT]$L[STARTS]"),
        '">
<tr><th title="datetime">End<td><input name="ENDS" value="',
        h($L["ENDS"]),
        '">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',
        h($L["INTERVAL_VALUE"]),
        '" class="size"> ',
        html_select("INTERVAL_FIELD", $Jd, $L["INTERVAL_FIELD"]),
        "<tr><th>Status<td>",
        html_select("STATUS", $eh, $L["STATUS"]),
        '<tr><th>Comment<td><input name="EVENT_COMMENT" value="',
        h($L["EVENT_COMMENT"]),
        '" data-maxlength="64">
<tr><th><td>',
        checkbox(
            "ON_COMPLETION",
            "PRESERVE",
            $L["ON_COMPLETION"] == "PRESERVE",
            "On completion preserve",
        ),
        '</table>
<p>';
    textarea("EVENT_DEFINITION", $L["EVENT_DEFINITION"]);
    echo '<p>
<input type="submit" value="Save">
';
    if ($aa != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $aa));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["procedure"])) {
    $ba = $_GET["name"] ?: $_GET["procedure"];
    $yg = isset($_GET["function"]) ? "FUNCTION" : "PROCEDURE";
    $L = $_POST;
    $L["fields"] = (array) $L["fields"];
    if ($_POST && !process_fields($L["fields"]) && !$k) {
        $rf = routine($_GET["procedure"], $yg);
        $zh = "$L[name]_adminer_" . uniqid();
        foreach ($L["fields"] as $y => $l) {
            if ($l["field"] == "") {
                unset($L["fields"][$y]);
            }
        }
        drop_create(
            "DROP $yg " . routine_id($ba, $rf),
            create_routine($yg, $L),
            "DROP $yg " . routine_id($L["name"], $L),
            create_routine($yg, ["name" => $zh] + $L),
            "DROP $yg " . routine_id($zh, $L),
            substr(ME, 0, -1),
            "Routine has been dropped.",
            "Routine has been altered.",
            "Routine has been created.",
            $ba,
            $L["name"],
        );
    }
    page_header(
        $ba != ""
            ? (isset($_GET["function"])
                    ? "Alter function"
                    : "Alter procedure") .
                ": " .
                h($ba)
            : (isset($_GET["function"])
                ? "Create function"
                : "Create procedure"),
        $k,
    );
    if (!$_POST) {
        if ($ba == "") {
            $L["language"] = "sql";
        } else {
            $L = routine($_GET["procedure"], $yg);
            $L["name"] = $ba;
        }
    }
    $b = get_vals("SHOW CHARACTER SET");
    sort($b);
    $zg = routine_languages();
    echo $b
    ? "<datalist id='collations'>" . optionlist($b) . "</datalist>"
    : "",
        '
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',
        h($L["name"]),
        '" data-maxlength="64" autocapitalize="off">
',
        $zg
            ? "<label>" .
                "Language" .
                ": " .
                html_select("language", $zg, $L["language"]) .
                "</label>\n"
            : "",
        '<input type="submit" value="Save">
<div class="scrollable">
<table class="nowrap">
';
    edit_fields($L["fields"], $b, $yg);
    if (isset($_GET["function"])) {
        echo "<tr><td>" . "Return type";
        edit_type(
            "returns",
            $L["returns"],
            $b,
            [],
            JUSH == "pgsql" ? ["void", "trigger"] : [],
        );
    }
    echo '</table>
',
        script("editFields();"),
        '</div>
<p>';
    textarea("definition", $L["definition"]);
    echo '<p>
<input type="submit" value="Save">
';
    if ($ba != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $ba));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["check"])) {
    $a = $_GET["check"];
    $D = $_GET["name"];
    $L = $_POST;
    if ($L && !$k) {
        if (JUSH == "sqlite") {
            $J = recreate_table(
                $a,
                $a,
                [],
                [],
                [],
                "",
                [],
                "$D",
                $L["drop"] ? "" : $L["clause"],
            );
        } else {
            $J =
                $D == "" ||
                queries(
                    "ALTER TABLE " .
                        table($a) .
                        " DROP CONSTRAINT " .
                        idf_escape($D),
                );
            if (!$L["drop"]) {
                $J = queries(
                    "ALTER TABLE " .
                        table($a) .
                        " ADD" .
                        ($L["name"] != ""
                            ? " CONSTRAINT " . idf_escape($L["name"])
                            : "") .
                        " CHECK ($L[clause])",
                );
            }
        }
        queries_redirect(
            ME . "table=" . urlencode($a),
            $L["drop"]
                ? "Check has been dropped."
                : ($D != ""
                    ? "Check has been altered."
                    : "Check has been created."),
            $J,
        );
    }
    page_header($D != "" ? "Alter check" . ": " . h($D) : "Create check", $k, [
        "table" => $a,
    ]);
    if (!$L) {
        $Ta = driver()->checkConstraints($a);
        $L = ["name" => $D, "clause" => $Ta[$D]];
    }
    echo '
<form action="" method="post">
<p>';
    if (JUSH != "sqlite") {
        echo "Name" .
            ': <input name="name" value="' .
            h($L["name"]) .
            '" data-maxlength="64" autocapitalize="off"> ';
    }
    echo doc_link(
    [
        "sql" => "create-table-check-constraints.html",
        "mariadb" => "constraint/",
    ],
    "?",
),
        "<p>";
    textarea("clause", $L["clause"]);
    echo '<p><input type="submit" value="Save">
';
    if ($D != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $D));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["trigger"])) {
    $a = $_GET["trigger"];
    $D = "$_GET[name]";
    $Th = trigger_options();
    $L = (array) trigger($D, $a) + ["Trigger" => $a . "_bi"];
    if ($_POST) {
        if (
            !$k &&
            in_array($_POST["Timing"], $Th["Timing"]) &&
            in_array($_POST["Event"], $Th["Event"]) &&
            in_array($_POST["Type"], $Th["Type"])
        ) {
            $df = " ON " . table($a);
            $Vb =
                "DROP TRIGGER " . idf_escape($D) . (JUSH == "pgsql" ? $df : "");
            $A = ME . "table=" . urlencode($a);
            if ($_POST["drop"]) {
                query_redirect($Vb, $A, "Trigger has been dropped.");
            } else {
                if ($D != "") {
                    queries($Vb);
                }
                queries_redirect(
                    $A,
                    $D != ""
                        ? "Trigger has been altered."
                        : "Trigger has been created.",
                    queries(create_trigger($df, $_POST)),
                );
                if ($D != "") {
                    queries(
                        create_trigger(
                            $df,
                            $L + ["Type" => reset($Th["Type"])],
                        ),
                    );
                }
            }
        }
        $L = $_POST;
    }
    page_header(
        $D != "" ? "Alter trigger" . ": " . h($D) : "Create trigger",
        $k,
        ["table" => $a],
    );
    echo '
<form action="" method="post" id="form">
<table class="layout">
<tr><th>Time<td>',
        html_select(
            "Timing",
            $Th["Timing"],
            $L["Timing"],
            "triggerChange(/^" .
                preg_quote($a, "/") .
                "_[ba][iud]$/, '" .
                js_escape($a) .
                "', this.form);",
        ),
        "<tr><th>Event<td>",
        html_select(
            "Event",
            $Th["Event"],
            $L["Event"],
            "this.form['Timing'].onchange();",
        ),
        in_array("UPDATE OF", $Th["Event"])
            ? " <input name='Of' value='" . h($L["Of"]) . "' class='hidden'>"
            : "",
        "<tr><th>Type<td>",
        html_select("Type", $Th["Type"], $L["Type"]),
        '</table>
<p>Name: <input name="Trigger" value="',
        h($L["Trigger"]),
        '" data-maxlength="64" autocapitalize="off">
',
        script("qs('#form')['Timing'].onchange();"),
        "<p>";
    textarea("Statement", $L["Statement"]);
    echo '<p>
<input type="submit" value="Save">
';
    if ($D != "") {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", $D));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["user"])) {
    $da = $_GET["user"];
    $ag = ["" => ["All privileges" => ""]];
    foreach (get_rows("SHOW PRIVILEGES") as $L) {
        foreach (
            explode(",", $L["Privilege"] == "Grant option" ? "" : $L["Context"])
            as $ob
        ) {
            $ag[$ob][$L["Privilege"]] = $L["Comment"];
        }
    }
    $ag["Server Admin"] += $ag["File access on server"];
    $ag["Databases"]["Create routine"] = $ag["Procedures"]["Create routine"];
    unset($ag["Procedures"]["Create routine"]);
    $ag["Columns"] = [];
    foreach (["Select", "Insert", "Update", "References"] as $X) {
        $ag["Columns"][$X] = $ag["Tables"][$X];
    }
    unset($ag["Server Admin"]["Usage"]);
    foreach ($ag["Tables"] as $y => $X) {
        unset($ag["Databases"][$y]);
    }
    $Ne = [];
    if ($_POST) {
        foreach ($_POST["objects"] as $y => $X) {
            $Ne[$X] = (array) $Ne[$X] + idx($_POST["grants"], $y, []);
        }
    }
    $bd = [];
    $bf = "";
    if (
        isset($_GET["host"]) &&
        ($J = connection()->query(
            "SHOW GRANTS FOR " . q($da) . "@" . q($_GET["host"]),
        ))
    ) {
        while ($L = $J->fetch_row()) {
            if (
                preg_match("~GRANT (.*) ON (.*) TO ~", $L[0], $B) &&
                preg_match_all(
                    "~ *([^(,]*[^ ,(])( *\([^)]+\))?~",
                    $B[1],
                    $qe,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($qe as $X) {
                    if ($X[1] != "USAGE") {
                        $bd["$B[2]$X[2]"][$X[1]] = true;
                    }
                    if (preg_match("~ WITH GRANT OPTION~", $L[0])) {
                        $bd["$B[2]$X[2]"]["GRANT OPTION"] = true;
                    }
                }
            }
            if (preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~", $L[0], $B)) {
                $bf = $B[1];
            }
        }
    }
    if ($_POST && !$k) {
        $cf = isset($_GET["host"]) ? q($da) . "@" . q($_GET["host"]) : "''";
        if ($_POST["drop"]) {
            query_redirect(
                "DROP USER $cf",
                ME . "privileges=",
                "User has been dropped.",
            );
        } else {
            $Pe = q($_POST["user"]) . "@" . q($_POST["host"]);
            $Gf = $_POST["pass"];
            if ($Gf != "" && !$_POST["hashed"] && !min_version(8)) {
                $Gf = get_val("SELECT PASSWORD(" . q($Gf) . ")");
                $k = !$Gf;
            }
            $tb = false;
            if (!$k) {
                if ($cf != $Pe) {
                    $tb = queries(
                        (min_version(5)
                            ? "CREATE USER"
                            : "GRANT USAGE ON *.* TO") .
                            " $Pe IDENTIFIED BY " .
                            (min_version(8) ? "" : "PASSWORD ") .
                            q($Gf),
                    );
                    $k = !$tb;
                } elseif ($Gf != $bf) {
                    queries("SET PASSWORD FOR $Pe = " . q($Gf));
                }
            }
            if (!$k) {
                $vg = [];
                foreach ($Ne as $We => $ad) {
                    if (isset($_GET["grant"])) {
                        $ad = array_filter($ad);
                    }
                    $ad = array_keys($ad);
                    if (isset($_GET["grant"])) {
                        $vg = array_diff(
                            array_keys(array_filter($Ne[$We], "strlen")),
                            $ad,
                        );
                    } elseif ($cf == $Pe) {
                        $Ze = array_keys((array) $bd[$We]);
                        $vg = array_diff($Ze, $ad);
                        $ad = array_diff($ad, $Ze);
                        unset($bd[$We]);
                    }
                    if (
                        preg_match('~^(.+)\s*(\(.*\))?$~U', $We, $B) &&
                        (!grant("REVOKE", $vg, $B[2], " ON $B[1] FROM $Pe") ||
                            !grant("GRANT", $ad, $B[2], " ON $B[1] TO $Pe"))
                    ) {
                        $k = true;
                        break;
                    }
                }
            }
            if (!$k && isset($_GET["host"])) {
                if ($cf != $Pe) {
                    queries("DROP USER $cf");
                } elseif (!isset($_GET["grant"])) {
                    foreach ($bd as $We => $vg) {
                        if (preg_match('~^(.+)(\(.*\))?$~U', $We, $B)) {
                            grant(
                                "REVOKE",
                                array_keys($vg),
                                $B[2],
                                " ON $B[1] FROM $Pe",
                            );
                        }
                    }
                }
            }
            queries_redirect(
                ME . "privileges=",
                isset($_GET["host"])
                    ? "User has been altered."
                    : "User has been created.",
                !$k,
            );
            if ($tb) {
                connection()->query("DROP USER $Pe");
            }
        }
    }
    page_header(
        isset($_GET["host"])
            ? "Username" . ": " . h("$da@$_GET[host]")
            : "Create user",
        $k,
        ["privileges" => ["", "Privileges"]],
    );
    $L = $_POST;
    if ($L) {
        $bd = $Ne;
    } else {
        $L = $_GET + [
            "host" => get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"),
        ];
        $L["pass"] = $bf;
        if ($bf != "") {
            $L["hashed"] = true;
        }
        $bd[
            (DB == "" || $bd ? "" : idf_escape(addcslashes(DB, "%_\\"))) . ".*"
        ] = [];
    }
    echo '<form action="" method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',
        h($L["host"]),
        '" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',
        h($L["user"]),
        '" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',
        h($L["pass"]),
        '" autocomplete="new-password">
',
        $L["hashed"] ? "" : script("typePassword(qs('#pass'));"),
        min_version(8)
            ? ""
            : checkbox(
                "hashed",
                1,
                $L["hashed"],
                "Hashed",
                "typePassword(this.form['pass'], this.checked);",
            ),
        '</table>

',
        "<table class='odds'>\n",
        "<thead><tr><th colspan='2'>" .
            "Privileges" .
            doc_link(["sql" => "grant.html#priv_level"]);
    $t = 0;
    foreach ($bd as $We => $ad) {
        echo "<th>" .
            ($We != "*.*"
                ? "<input name='objects[$t]' value='" .
                    h($We) .
                    "' size='10' autocapitalize='off'>"
                : input_hidden("objects[$t]", "*.*") . "*.*");
        $t++;
    }
    echo "</thead>\n";
    foreach (
        [
            "" => "",
            "Server Admin" => "Server",
            "Databases" => "Database",
            "Tables" => "Table",
            "Columns" => "Column",
            "Procedures" => "Routine",
        ]
        as $ob => $Lb
    ) {
        foreach ((array) $ag[$ob] as $Zf => $fb) {
            echo "<tr><td" .
                ($Lb ? ">$Lb<td" : " colspan='2'") .
                ' lang="en" title="' .
                h($fb) .
                '">' .
                h($Zf);
            $t = 0;
            foreach ($bd as $We => $ad) {
                $D = "'grants[$t][" . h(strtoupper($Zf)) . "]'";
                $Y = $ad[strtoupper($Zf)];
                if (
                    $ob == "Server Admin" &&
                    $We != (isset($bd["*.*"]) ? "*.*" : ".*")
                ) {
                    echo "<td>";
                } elseif (isset($_GET["grant"])) {
                    echo "<td><select name=$D><option><option value='1'" .
                        ($Y ? " selected" : "") .
                        ">" .
                        "Grant" .
                        "<option value='0'" .
                        ($Y == "0" ? " selected" : "") .
                        ">" .
                        "Revoke" .
                        "</select>";
                } else {
                    echo "<td align='center'><label class='block'>",
                        "<input type='checkbox' name=$D value='1'" .
                            ($Y ? " checked" : "") .
                            ($Zf == "All privileges"
                                ? " id='grants-$t-all'>"
                                : ">" .
                                    ($Zf == "Grant option"
                                        ? ""
                                        : script(
                                            "qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$t-all'); };",
                                        ))),
                        "</label>";
                }
                $t++;
            }
        }
    }
    echo "</table>\n",
        '<p>
<input type="submit" value="Save">
';
    if (isset($_GET["host"])) {
        echo '<input type="submit" name="drop" value="Drop">',
            confirm(sprintf("Drop %s?", "$da@$_GET[host]"));
    }
    echo input_token(),
        '</form>
';
} elseif (isset($_GET["processlist"])) {
    if (support("kill")) {
        if ($_POST && !$k) {
            $Wd = 0;
            foreach ((array) $_POST["kill"] as $X) {
                if (kill_process($X)) {
                    $Wd++;
                }
            }
            queries_redirect(
                ME . "processlist=",
                lang_format(
                    [
                        "%d process has been killed.",
                        "%d processes have been killed.",
                    ],
                    $Wd,
                ),
                $Wd || !$_POST["kill"],
            );
        }
    }
    page_header("Process list", $k);
    echo '
<form action="" method="post">
<div class="scrollable">
<table class="nowrap checkable odds">
',
        script(
            "mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});",
        );
    $t = -1;
    foreach (process_list() as $t => $L) {
        if (!$t) {
            echo "<thead><tr lang='en'>" . (support("kill") ? "<th>" : "");
            foreach ($L as $y => $X) {
                echo "<th>$y" .
                    doc_link([
                        "sql" =>
                            "show-processlist.html#processlist_" .
                            strtolower($y),
                    ]);
            }
            echo "</thead>\n";
        }
        echo "<tr>" .
            (support("kill")
                ? "<td>" .
                    checkbox("kill[]", $L[JUSH == "sql" ? "Id" : "pid"], 0)
                : "");
        foreach ($L as $y => $X) {
            echo "<td>" .
                ((JUSH == "sql" &&
                    $y == "Info" &&
                    preg_match("~Query|Killed~", $L["Command"]) &&
                    $X != "") ||
                (JUSH == "pgsql" && $y == "current_query" && $X != "<IDLE>") ||
                (JUSH == "oracle" && $y == "sql_text" && $X != "")
                    ? "<code class='jush-" .
                        JUSH .
                        "'>" .
                        shorten_utf8($X, 100, "</code>") .
                        ' <a href="' .
                        h(
                            ME .
                                ($L["db"] != ""
                                    ? "db=" . urlencode($L["db"]) . "&"
                                    : "") .
                                "sql=" .
                                urlencode($X),
                        ) .
                        '">' .
                        "Clone" .
                        "</a>"
                    : h($X));
        }
        echo "\n";
    }
    echo '</table>
</div>
<p>
';
    if (support("kill")) {
        echo $t + 1 . "/" . sprintf("%d in total", max_connections()),
            "<p><input type='submit' value='" . "Kill" . "'>\n";
    }
    echo input_token(),
        '</form>
',
        script("tableCheck();");
} elseif (isset($_GET["select"])) {
    $a = $_GET["select"];
    $S = table_status1($a);
    $x = indexes($a);
    $m = fields($a);
    $p = column_foreign_keys($a);
    $Ye = $S["Oid"];
    $ia = get_settings("adminer_import");
    $wg = [];
    $d = [];
    $Eg = [];
    $nf = [];
    $Bh = "";
    foreach ($m as $y => $l) {
        $D = adminer()->fieldName($l);
        $Le = html_entity_decode(strip_tags($D), ENT_QUOTES);
        if (isset($l["privileges"]["select"]) && $D != "") {
            $d[$y] = $Le;
            if (is_shortable($l)) {
                $Bh = adminer()->selectLengthProcess();
            }
        }
        if (isset($l["privileges"]["where"]) && $D != "") {
            $Eg[$y] = $Le;
        }
        if (isset($l["privileges"]["order"]) && $D != "") {
            $nf[$y] = $Le;
        }
        $wg += $l["privileges"];
    }
    [$O, $s] = adminer()->selectColumnsProcess($d, $x);
    $O = array_unique($O);
    $s = array_unique($s);
    $Nd = count($s) < count($O);
    $Z = adminer()->selectSearchProcess($m, $x);
    $mf = adminer()->selectOrderProcess($m, $x);
    $z = adminer()->selectLimitProcess();
    if ($_GET["val"] && is_ajax()) {
        header("Content-Type: text/plain; charset=utf-8");
        foreach ($_GET["val"] as $bi => $L) {
            $sa = convert_field($m[key($L)]);
            $O = [$sa ?: idf_escape(key($L))];
            $Z[] = where_check($bi, $m);
            $K = driver()->select($a, $O, $Z, $O);
            if ($K) {
                echo first($K->fetch_row());
            }
        }
        exit();
    }
    $Wf = $di = [];
    foreach ($x as $w) {
        if ($w["type"] == "PRIMARY") {
            $Wf = array_flip($w["columns"]);
            $di = $O ? $Wf : [];
            foreach ($di as $y => $X) {
                if (in_array(idf_escape($y), $O)) {
                    unset($di[$y]);
                }
            }
            break;
        }
    }
    if ($Ye && !$Wf) {
        $Wf = $di = [$Ye => 0];
        $x[] = ["type" => "PRIMARY", "columns" => [$Ye]];
    }
    if ($_POST && !$k) {
        $zi = $Z;
        if (!$_POST["all"] && is_array($_POST["check"])) {
            $Ta = [];
            foreach ($_POST["check"] as $Qa) {
                $Ta[] = where_check($Qa, $m);
            }
            $zi[] = "((" . implode(") OR (", $Ta) . "))";
        }
        $zi = $zi ? "\nWHERE " . implode(" AND ", $zi) : "";
        if ($_POST["export"]) {
            save_settings(
                ["output" => $_POST["output"], "format" => $_POST["format"]],
                "adminer_import",
            );
            dump_headers($a);
            adminer()->dumpTable($a, "");
            $Vc =
                ($O ? implode(", ", $O) : "*") .
                convert_fields($d, $m, $O) .
                "\nFROM " .
                table($a);
            $dd =
                ($s && $Nd ? "\nGROUP BY " . implode(", ", $s) : "") .
                ($mf ? "\nORDER BY " . implode(", ", $mf) : "");
            $I = "SELECT $Vc$zi$dd";
            if (is_array($_POST["check"]) && !$Wf) {
                $Zh = [];
                foreach ($_POST["check"] as $X) {
                    $Zh[] =
                        "(SELECT" .
                        limit(
                            $Vc,
                            "\nWHERE " .
                                ($Z ? implode(" AND ", $Z) . " AND " : "") .
                                where_check($X, $m) .
                                $dd,
                            1,
                        ) .
                        ")";
                }
                $I = implode(" UNION ALL ", $Zh);
            }
            adminer()->dumpData($a, "table", $I);
            adminer()->dumpFooter();
            exit();
        }
        if (!adminer()->selectEmailProcess($Z, $p)) {
            if ($_POST["save"] || $_POST["delete"]) {
                $J = true;
                $ja = 0;
                $Q = [];
                if (!$_POST["delete"]) {
                    foreach ($_POST["fields"] as $D => $X) {
                        $X = process_input($m[$D]);
                        if ($X !== null && ($_POST["clone"] || $X !== false)) {
                            $Q[idf_escape($D)] =
                                $X !== false ? $X : idf_escape($D);
                        }
                    }
                }
                if ($_POST["delete"] || $Q) {
                    $I = $_POST["clone"]
                        ? "INTO " .
                            table($a) .
                            " (" .
                            implode(", ", array_keys($Q)) .
                            ")\nSELECT " .
                            implode(", ", $Q) .
                            "\nFROM " .
                            table($a)
                        : "";
                    if (
                        $_POST["all"] ||
                        ($Wf && is_array($_POST["check"])) ||
                        $Nd
                    ) {
                        $J = $_POST["delete"]
                            ? driver()->delete($a, $zi)
                            : ($_POST["clone"]
                                ? queries(
                                    "INSERT $I$zi" .
                                        driver()->insertReturning($a),
                                )
                                : driver()->update($a, $Q, $zi));
                        $ja = connection()->affected_rows;
                        if (is_object($J)) {
                            $ja += $J->num_rows;
                        }
                    } else {
                        foreach ((array) $_POST["check"] as $X) {
                            $yi =
                                "\nWHERE " .
                                ($Z ? implode(" AND ", $Z) . " AND " : "") .
                                where_check($X, $m);
                            $J = $_POST["delete"]
                                ? driver()->delete($a, $yi, 1)
                                : ($_POST["clone"]
                                    ? queries("INSERT" . limit1($a, $I, $yi))
                                    : driver()->update($a, $Q, $yi, 1));
                            if (!$J) {
                                break;
                            }
                            $ja += connection()->affected_rows;
                        }
                    }
                }
                $C = lang_format(
                    [
                        "%d item has been affected.",
                        "%d items have been affected.",
                    ],
                    $ja,
                );
                if ($_POST["clone"] && $J && $ja == 1) {
                    $be = last_id($J);
                    if ($be) {
                        $C = sprintf("Item%s has been inserted.", " $be");
                    }
                }
                queries_redirect(
                    remove_from_uri(
                        $_POST["all"] && $_POST["delete"] ? "page" : "",
                    ),
                    $C,
                    $J,
                );
                if (!$_POST["delete"]) {
                    $Sf = (array) $_POST["fields"];
                    edit_form(
                        $a,
                        array_intersect_key($m, $Sf),
                        $Sf,
                        !$_POST["clone"],
                        $k,
                    );
                    page_footer();
                    exit();
                }
            } elseif (!$_POST["import"]) {
                if (!$_POST["val"]) {
                    $k = "Ctrl+click on a value to modify it.";
                } else {
                    $J = true;
                    $ja = 0;
                    foreach ($_POST["val"] as $bi => $L) {
                        $Q = [];
                        foreach ($L as $y => $X) {
                            $y = bracket_escape($y, true);
                            $Q[idf_escape($y)] =
                                preg_match("~char|text~", $m[$y]["type"]) ||
                                $X != ""
                                    ? adminer()->processInput($m[$y], $X)
                                    : "NULL";
                        }
                        $J = driver()->update(
                            $a,
                            $Q,
                            " WHERE " .
                                ($Z ? implode(" AND ", $Z) . " AND " : "") .
                                where_check($bi, $m),
                            $Nd || $Wf ? 0 : 1,
                            " ",
                        );
                        if (!$J) {
                            break;
                        }
                        $ja += connection()->affected_rows;
                    }
                    queries_redirect(
                        remove_from_uri(),
                        lang_format(
                            [
                                "%d item has been affected.",
                                "%d items have been affected.",
                            ],
                            $ja,
                        ),
                        $J,
                    );
                }
            } elseif (!is_string($Jc = get_file("csv_file", true))) {
                $k = upload_error($Jc);
            } elseif (!preg_match("~~u", $Jc)) {
                $k = "File must be in UTF-8 encoding.";
            } else {
                save_settings(
                    [
                        "output" => $ia["output"],
                        "format" => $_POST["separator"],
                    ],
                    "adminer_import",
                );
                $J = true;
                $cb = array_keys($m);
                preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~', $Jc, $qe);
                $ja = count($qe[0]);
                driver()->begin();
                $Kg =
                    $_POST["separator"] == "csv"
                        ? ","
                        : ($_POST["separator"] == "tsv"
                            ? "\t"
                            : ";");
                $M = [];
                foreach ($qe[0] as $y => $X) {
                    preg_match_all(
                        "~((?>\"[^\"]*\")+|[^$Kg]*)$Kg~",
                        $X . $Kg,
                        $re,
                    );
                    if (!$y && !array_diff($re[1], $cb)) {
                        $cb = $re[1];
                        $ja--;
                    } else {
                        $Q = [];
                        foreach ($re[1] as $t => $Za) {
                            $Q[idf_escape($cb[$t])] =
                                $Za == "" && $m[$cb[$t]]["null"]
                                    ? "NULL"
                                    : q(
                                        preg_match('~^".*"$~s', $Za)
                                            ? str_replace(
                                                '""',
                                                '"',
                                                substr($Za, 1, -1),
                                            )
                                            : $Za,
                                    );
                        }
                        $M[] = $Q;
                    }
                }
                $J = !$M || driver()->insertUpdate($a, $M, $Wf);
                if ($J) {
                    driver()->commit();
                }
                queries_redirect(
                    remove_from_uri("page"),
                    lang_format(
                        [
                            "%d row has been imported.",
                            "%d rows have been imported.",
                        ],
                        $ja,
                    ),
                    $J,
                );
                driver()->rollback();
            }
        }
    }
    $ph = adminer()->tableName($S);
    if (is_ajax()) {
        page_headers();
        ob_start();
    } else {
        page_header("Select" . ": $ph", $k);
    }
    $Q = null;
    if (isset($wg["insert"]) || !support("table")) {
        $F = [];
        foreach ((array) $_GET["where"] as $X) {
            if (
                isset($p[$X["col"]]) &&
                count($p[$X["col"]]) == 1 &&
                ($X["op"] == "=" ||
                    (!$X["op"] &&
                        (is_array($X["val"]) ||
                            !preg_match("~[_%]~", $X["val"]))))
            ) {
                $F["set" . "[" . bracket_escape($X["col"]) . "]"] = $X["val"];
            }
        }
        $Q = $F ? "&" . http_build_query($F) : "";
    }
    adminer()->selectLinks($S, $Q);
    if (!$d && support("table")) {
        echo "<p class='error'>" .
            "Unable to select the table" .
            ($m ? "." : ": " . error()) .
            "\n";
    } else {
        echo "<form action='' id='form'>\n", "<div style='display: none;'>";
        hidden_fields_get();
        echo DB != ""
        ? input_hidden("db", DB) .
            (isset($_GET["ns"]) ? input_hidden("ns", $_GET["ns"]) : "")
        : "",
            input_hidden("select", $a),
            "</div>\n";
        adminer()->selectColumnsPrint($O, $d);
        adminer()->selectSearchPrint($Z, $Eg, $x);
        adminer()->selectOrderPrint($mf, $nf, $x);
        adminer()->selectLimitPrint($z);
        adminer()->selectLengthPrint($Bh);
        adminer()->selectActionPrint($x);
        echo "</form>\n";
        $E = $_GET["page"];
        $Uc = null;
        if ($E == "last") {
            $Uc = get_val(count_rows($a, $Z, $Nd, $s));
            $E = floor(max(0, intval($Uc) - 1) / $z);
        }
        $Fg = $O;
        $cd = $s;
        if (!$Fg) {
            $Fg[] = "*";
            $pb = convert_fields($d, $m, $O);
            if ($pb) {
                $Fg[] = substr($pb, 2);
            }
        }
        foreach ($O as $y => $X) {
            $l = $m[idf_unescape($X)];
            if ($l && ($sa = convert_field($l))) {
                $Fg[$y] = "$sa AS $X";
            }
        }
        if (!$Nd && $di) {
            foreach ($di as $y => $X) {
                $Fg[] = idf_escape($y);
                if ($cd) {
                    $cd[] = idf_escape($y);
                }
            }
        }
        $J = driver()->select($a, $Fg, $Z, $cd, $mf, $z, $E, true);
        if (!$J) {
            echo "<p class='error'>" . error() . "\n";
        } else {
            if (JUSH == "mssql" && $E) {
                $J->seek($z * $E);
            }
            $hc = [];
            echo "<form action='' method='post' enctype='multipart/form-data'>\n";
            $M = [];
            while ($L = $J->fetch_assoc()) {
                if ($E && JUSH == "oracle") {
                    unset($L["RNUM"]);
                }
                $M[] = $L;
            }
            if ($_GET["page"] != "last" && $z && $s && $Nd && JUSH == "sql") {
                $Uc = get_val(" SELECT FOUND_ROWS()");
            }
            if (!$M) {
                echo "<p class='message'>" . "No rows." . "\n";
            } else {
                $Aa = adminer()->backwardKeys($a, $ph);
                echo "<div class='scrollable'>",
                    "<table id='table' class='nowrap checkable odds'>",
                    script(
                        "mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});",
                    ),
                    "<thead><tr>" .
                        (!$s && $O
                            ? ""
                            : "<td><input type='checkbox' id='all-page' class='jsonly'>" .
                                script(
                                    "qs('#all-page').onclick = partial(formCheck, /check/);",
                                    "",
                                ) .
                                " <a href='" .
                                h(
                                    $_GET["modify"]
                                        ? remove_from_uri("modify")
                                        : $_SERVER["REQUEST_URI"] . "&modify=1",
                                ) .
                                "'>" .
                                "Modify" .
                                "</a>");
                $Me = [];
                $Xc = [];
                reset($O);
                $ig = 1;
                foreach ($M[0] as $y => $X) {
                    if (!isset($di[$y])) {
                        $X = idx($_GET["columns"], key($O)) ?: [];
                        $l = $m[$O ? ($X ? $X["col"] : current($O)) : $y];
                        $D = $l
                            ? adminer()->fieldName($l, $ig)
                            : ($X["fun"]
                                ? "*"
                                : h($y));
                        if ($D != "") {
                            $ig++;
                            $Me[$y] = $D;
                            $c = idf_escape($y);
                            $sd =
                                remove_from_uri("(order|desc)[^=]*|page") .
                                "&order%5B0%5D=" .
                                urlencode($y);
                            $Lb = "&desc%5B0%5D=1";
                            echo "<th id='th[" .
                                h(bracket_escape($y)) .
                                "]'>" .
                                script(
                                    "mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});",
                                    "",
                                );
                            $Wc = apply_sql_function($X["fun"], $D);
                            $Ug = isset($l["privileges"]["order"]) || $Wc;
                            echo $Ug
                            ? '<a href="' .
                                h(
                                    $sd .
                                        ($mf[0] == $c ||
                                        $mf[0] == $y ||
                                        (!$mf && $Nd && $s[0] == $c)
                                            ? $Lb
                                            : ""),
                                ) .
                                '">' .
                                "$Wc</a>"
                            : $Wc,
                                "<span class='column hidden'>";
                            if ($Ug) {
                                echo "<a href='" .
                                    h($sd . $Lb) .
                                    "' title='" .
                                    "descending" .
                                    "' class='text'> ↓</a>";
                            }
                            if (
                                !$X["fun"] &&
                                isset($l["privileges"]["where"])
                            ) {
                                echo '<a href="#fieldset-search" title="' .
                                "Search" .
                                '" class="text jsonly"> =</a>',
                                    script(
                                        "qsl('a').onclick = partial(selectSearch, '" .
                                            js_escape($y) .
                                            "');",
                                    );
                            }
                            echo "</span>";
                        }
                        $Xc[$y] = $X["fun"];
                        next($O);
                    }
                }
                $he = [];
                if ($_GET["modify"]) {
                    foreach ($M as $L) {
                        foreach ($L as $y => $X) {
                            $he[$y] = max(
                                $he[$y],
                                min(40, strlen(utf8_decode($X))),
                            );
                        }
                    }
                }
                echo ($Aa ? "<th>" . "Relations" : "") . "</thead>\n";
                if (is_ajax()) {
                    ob_end_clean();
                }
                foreach (adminer()->rowDescriptions($M, $p) as $Ke => $L) {
                    $ai = unique_array($M[$Ke], $x);
                    if (!$ai) {
                        $ai = [];
                        foreach ($M[$Ke] as $y => $X) {
                            if (
                                !preg_match(
                                    '~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',
                                    $y,
                                )
                            ) {
                                $ai[$y] = $X;
                            }
                        }
                    }
                    $bi = "";
                    foreach ($ai as $y => $X) {
                        $l = (array) $m[$y];
                        if (
                            (JUSH == "sql" || JUSH == "pgsql") &&
                            preg_match("~char|text|enum|set~", $l["type"]) &&
                            strlen($X) > 64
                        ) {
                            $y = strpos($y, "(") ? $y : idf_escape($y);
                            $y =
                                "MD5(" .
                                (JUSH != "sql" ||
                                preg_match("~^utf8~", $l["collation"])
                                    ? $y
                                    : "CONVERT($y USING " .
                                        charset(connection()) .
                                        ")") .
                                ")";
                            $X = md5($X);
                        }
                        $bi .=
                            "&" .
                            ($X !== null
                                ? urlencode(
                                        "where[" . bracket_escape($y) . "]",
                                    ) .
                                    "=" .
                                    urlencode($X === false ? "f" : $X)
                                : "null%5B%5D=" . urlencode($y));
                    }
                    echo "<tr>" .
                        (!$s && $O
                            ? ""
                            : "<td>" .
                                checkbox(
                                    "check[]",
                                    substr($bi, 1),
                                    in_array(
                                        substr($bi, 1),
                                        (array) $_POST["check"],
                                    ),
                                ) .
                                ($Nd || information_schema(DB)
                                    ? ""
                                    : " <a href='" .
                                        h(ME . "edit=" . urlencode($a) . $bi) .
                                        "' class='edit'>" .
                                        "edit" .
                                        "</a>"));
                    foreach ($L as $y => $X) {
                        if (isset($Me[$y])) {
                            $l = (array) $m[$y];
                            $X = driver()->value($X, $l);
                            if (
                                $X != "" &&
                                (!isset($hc[$y]) || $hc[$y] != "")
                            ) {
                                $hc[$y] = is_mail($X) ? $Me[$y] : "";
                            }
                            $_ = "";
                            if (
                                preg_match(
                                    "~blob|bytea|raw|file~",
                                    $l["type"],
                                ) &&
                                $X != ""
                            ) {
                                $_ =
                                    ME .
                                    "download=" .
                                    urlencode($a) .
                                    "&field=" .
                                    urlencode($y) .
                                    $bi;
                            }
                            if (!$_ && $X !== null) {
                                foreach ((array) $p[$y] as $o) {
                                    if (
                                        count($p[$y]) == 1 ||
                                        end($o["source"]) == $y
                                    ) {
                                        $_ = "";
                                        foreach ($o["source"] as $t => $Vg) {
                                            $_ .= where_link(
                                                $t,
                                                $o["target"][$t],
                                                $M[$Ke][$Vg],
                                            );
                                        }
                                        $_ =
                                            ($o["db"] != ""
                                                ? preg_replace(
                                                    "~([?&]db=)[^&]+~",
                                                    '\1' . urlencode($o["db"]),
                                                    ME,
                                                )
                                                : ME) .
                                            "select=" .
                                            urlencode($o["table"]) .
                                            $_;
                                        if ($o["ns"]) {
                                            $_ = preg_replace(
                                                "~([?&]ns=)[^&]+~",
                                                '\1' . urlencode($o["ns"]),
                                                $_,
                                            );
                                        }
                                        if (count($o["source"]) == 1) {
                                            break;
                                        }
                                    }
                                }
                            }
                            if ($y == "COUNT(*)") {
                                $_ = ME . "select=" . urlencode($a);
                                $t = 0;
                                foreach ((array) $_GET["where"] as $W) {
                                    if (!array_key_exists($W["col"], $ai)) {
                                        $_ .= where_link(
                                            $t++,
                                            $W["col"],
                                            $W["val"],
                                            $W["op"],
                                        );
                                    }
                                }
                                foreach ($ai as $Td => $W) {
                                    $_ .= where_link($t++, $Td, $W);
                                }
                            }
                            $td = select_value($X, $_, $l, $Bh);
                            $u = h("val[$bi][" . bracket_escape($y) . "]");
                            $Tf = idx(
                                idx($_POST["val"], $bi),
                                bracket_escape($y),
                            );
                            $cc =
                                !is_array($L[$y]) &&
                                is_utf8($td) &&
                                $M[$Ke][$y] == $L[$y] &&
                                !$Xc[$y] &&
                                !$l["generated"];
                            $Ah = preg_match("~text|json|lob~", $l["type"]);
                            echo "<td id='$u'" .
                                (preg_match(number_type(), $l["type"]) &&
                                ($X === null || is_numeric(strip_tags($td)))
                                    ? " class='number'"
                                    : "");
                            if (
                                ($_GET["modify"] && $cc && $X !== null) ||
                                $Tf !== null
                            ) {
                                $gd = h($Tf !== null ? $Tf : $L[$y]);
                                echo ">" .
                                    ($Ah
                                        ? "<textarea name='$u' cols='30' rows='" .
                                            (substr_count($L[$y], "\n") + 1) .
                                            "'>$gd</textarea>"
                                        : "<input name='$u' value='$gd' size='$he[$y]'>");
                            } else {
                                $me = strpos($td, "<i>…</i>");
                                echo " data-text='" .
                                    ($me ? 2 : ($Ah ? 1 : 0)) .
                                    "'" .
                                    ($cc
                                        ? ""
                                        : " data-warning='" .
                                            h(
                                                "Use edit link to modify this value.",
                                            ) .
                                            "'") .
                                    ">$td";
                            }
                        }
                    }
                    if ($Aa) {
                        echo "<td>";
                    }
                    adminer()->backwardKeysPrint($Aa, $M[$Ke]);
                    echo "</tr>\n";
                }
                if (is_ajax()) {
                    exit();
                }
                echo "</table>\n", "</div>\n";
            }
            if (!is_ajax()) {
                if ($M || $E) {
                    $vc = true;
                    if ($_GET["page"] != "last") {
                        if (!$z || (count($M) < $z && ($M || !$E))) {
                            $Uc = ($E ? $E * $z : 0) + count($M);
                        } elseif (JUSH != "sql" || !$Nd) {
                            $Uc = $Nd ? false : found_rows($S, $Z);
                            if (intval($Uc) < max(1e4, 2 * ($E + 1) * $z)) {
                                $Uc = first(
                                    slow_query(count_rows($a, $Z, $Nd, $s)),
                                );
                            } else {
                                $vc = false;
                            }
                        }
                    }
                    $_f = $z && ($Uc === false || $Uc > $z || $E);
                    if ($_f) {
                        echo ($Uc === false ? count($M) + 1 : $Uc - $E * $z) >
                    $z
                        ? '<p><a href="' .
                            h(remove_from_uri("page") . "&page=" . ($E + 1)) .
                            '" class="loadmore">' .
                            "Load more data" .
                            "</a>" .
                            script(
                                "qsl('a').onclick = partial(selectLoadMore, $z, '" .
                                    "Loading" .
                                    "…');",
                                "",
                            )
                        : "",
                            "\n";
                    }
                    echo "<div class='footer'><div>\n";
                    if ($_f) {
                        $te =
                            $Uc === false
                                ? $E + (count($M) >= $z ? 2 : 1)
                                : floor(($Uc - 1) / $z);
                        echo "<fieldset>";
                        if (JUSH != "simpledb") {
                            echo "<legend><a href='" .
                            h(remove_from_uri("page")) .
                            "'>" .
                            "Page" .
                            "</a></legend>",
                                script(
                                    "qsl('a').onclick = function () { pageClick(this.href, +prompt('" .
                                        "Page" .
                                        "', '" .
                                        ($E + 1) .
                                        "')); return false; };",
                                ),
                                pagination(0, $E) . ($E > 5 ? " …" : "");
                            for (
                                $t = max(1, $E - 4);
                                $t < min($te, $E + 5);
                                $t++
                            ) {
                                echo pagination($t, $E);
                            }
                            if ($te > 0) {
                                echo $E + 5 < $te ? " …" : "",
                                    $vc && $Uc !== false
                                        ? pagination($te, $E)
                                        : " <a href='" .
                                            h(
                                                remove_from_uri("page") .
                                                    "&page=last",
                                            ) .
                                            "' title='~$te'>" .
                                            "last" .
                                            "</a>";
                            }
                        } else {
                            echo "<legend>" . "Page" . "</legend>",
                                pagination(0, $E) . ($E > 1 ? " …" : ""),
                                $E ? pagination($E, $E) : "",
                                $te > $E
                                    ? pagination($E + 1, $E) .
                                        ($te > $E + 1 ? " …" : "")
                                    : "";
                        }
                        echo "</fieldset>\n";
                    }
                    echo "<fieldset>",
                        "<legend>" . "Whole result" . "</legend>";
                    $Sb = ($vc ? "" : "~ ") . $Uc;
                    $gf = "const checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$Sb' : checked); selectCount('selected2', this.checked || !checked ? '$Sb' : checked);";
                    echo checkbox(
                    "all",
                    1,
                    0,
                    $Uc !== false
                        ? ($vc ? "" : "~ ") .
                            lang_format(["%d row", "%d rows"], $Uc)
                        : "",
                    $gf,
                ) . "\n",
                        "</fieldset>\n";
                    if (adminer()->selectCommandPrint()) {
                        echo "<fieldset",
                            $_GET["modify"] ? "" : ' class="jsonly"',
                            '><legend>Modify</legend><div>
<input type="submit" value="Save"',
                            $_GET["modify"]
                                ? ""
                                : ' title="' .
                                    "Ctrl+click on a value to modify it." .
                                    '"',
                            '>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',
                            confirm(),
                            '</div></fieldset>
';
                    }
                    $Sc = adminer()->dumpFormat();
                    foreach ((array) $_GET["columns"] as $c) {
                        if ($c["fun"]) {
                            unset($Sc["sql"]);
                            break;
                        }
                    }
                    if ($Sc) {
                        print_fieldset(
                            "export",
                            "Export" . " <span id='selected2'></span>",
                        );
                        $yf = adminer()->dumpOutput();
                        echo $yf
                        ? html_select("output", $yf, $ia["output"]) . " "
                        : "",
                            html_select("format", $Sc, $ia["format"]),
                            " <input type='submit' name='export' value='" .
                                "Export" .
                                "'>\n",
                            "</div></fieldset>\n";
                    }
                    adminer()->selectEmailPrint(
                        array_filter($hc, "strlen"),
                        $d,
                    );
                    echo "</div></div>\n";
                }
                if (adminer()->selectImportPrint()) {
                    echo "<p>",
                        "<a href='#import'>" . "Import" . "</a>",
                        script(
                            "qsl('a').onclick = partial(toggle, 'import');",
                            "",
                        ),
                        "<span id='import'" .
                            ($_POST["import"] ? "" : " class='hidden'") .
                            ">: ",
                        "<input type='file' name='csv_file'> ",
                        html_select(
                            "separator",
                            ["csv" => "CSV,", "csv;" => "CSV;", "tsv" => "TSV"],
                            $ia["format"],
                        ),
                        " <input type='submit' name='import' value='" .
                            "Import" .
                            "'>",
                        "</span>";
                }
                echo input_token(),
                    "</form>\n",
                    !$s && $O ? "" : script("tableCheck();");
            }
        }
    }
    if (is_ajax()) {
        ob_end_clean();
        exit();
    }
} elseif (isset($_GET["variables"])) {
    $dh = isset($_GET["status"]);
    page_header($dh ? "Status" : "Variables");
    $pi = $dh ? show_status() : show_variables();
    if (!$pi) {
        echo "<p class='message'>" . "No rows." . "\n";
    } else {
        echo "<table>\n";
        foreach ($pi as $L) {
            echo "<tr>";
            $y = array_shift($L);
            echo "<th><code class='jush-" .
                JUSH .
                ($dh ? "status" : "set") .
                "'>" .
                h($y) .
                "</code>";
            foreach ($L as $X) {
                echo "<td>" . nl_br(h($X));
            }
        }
        echo "</table>\n";
    }
} elseif (isset($_GET["script"])) {
    header("Content-Type: text/javascript; charset=utf-8");
    if ($_GET["script"] == "db") {
        $mh = ["Data_length" => 0, "Index_length" => 0, "Data_free" => 0];
        foreach (table_status() as $D => $S) {
            json_row("Comment-$D", h($S["Comment"]));
            if (!is_view($S)) {
                foreach (["Engine", "Collation"] as $y) {
                    json_row("$y-$D", h($S[$y]));
                }
                foreach (
                    $mh + ["Auto_increment" => 0, "Rows" => 0]
                    as $y => $X
                ) {
                    if ($S[$y] != "") {
                        $X = format_number($S[$y]);
                        if ($X >= 0) {
                            json_row(
                                "$y-$D",
                                $y == "Rows" &&
                                $X &&
                                $S["Engine"] ==
                                    (JUSH == "pgsql" ? "table" : "InnoDB")
                                    ? "~ $X"
                                    : $X,
                            );
                        }
                        if (isset($mh[$y])) {
                            $mh[$y] +=
                                $S["Engine"] != "InnoDB" || $y != "Data_free"
                                    ? $S[$y]
                                    : 0;
                        }
                    } elseif (array_key_exists($y, $S)) {
                        json_row("$y-$D", "?");
                    }
                }
            }
        }
        foreach ($mh as $y => $X) {
            json_row("sum-$y", format_number($X));
        }
        json_row("");
    } elseif ($_GET["script"] == "kill") {
        connection()->query("KILL " . number($_POST["kill"]));
    } else {
        foreach (count_tables(adminer()->databases()) as $i => $X) {
            json_row("tables-$i", $X);
            json_row("size-$i", db_size($i));
        }
        json_row("");
    }
    exit();
} else {
    $vh = array_merge((array) $_POST["tables"], (array) $_POST["views"]);
    if ($vh && !$k && !$_POST["search"]) {
        $J = true;
        $C = "";
        if (
            JUSH == "sql" &&
            $_POST["tables"] &&
            count($_POST["tables"]) > 1 &&
            ($_POST["drop"] || $_POST["truncate"] || $_POST["copy"])
        ) {
            queries("SET foreign_key_checks = 0");
        }
        if ($_POST["truncate"]) {
            if ($_POST["tables"]) {
                $J = truncate_tables($_POST["tables"]);
            }
            $C = "Tables have been truncated.";
        } elseif ($_POST["move"]) {
            $J = move_tables(
                (array) $_POST["tables"],
                (array) $_POST["views"],
                $_POST["target"],
            );
            $C = "Tables have been moved.";
        } elseif ($_POST["copy"]) {
            $J = copy_tables(
                (array) $_POST["tables"],
                (array) $_POST["views"],
                $_POST["target"],
            );
            $C = "Tables have been copied.";
        } elseif ($_POST["drop"]) {
            if ($_POST["views"]) {
                $J = drop_views($_POST["views"]);
            }
            if ($J && $_POST["tables"]) {
                $J = drop_tables($_POST["tables"]);
            }
            $C = "Tables have been dropped.";
        } elseif (JUSH == "sqlite" && $_POST["check"]) {
            foreach ((array) $_POST["tables"] as $R) {
                foreach (
                    get_rows("PRAGMA integrity_check(" . q($R) . ")")
                    as $L
                ) {
                    $C .=
                        "<b>" .
                        h($R) .
                        "</b>: " .
                        h($L["integrity_check"]) .
                        "<br>";
                }
            }
        } elseif (JUSH != "sql") {
            $J =
                JUSH == "sqlite"
                    ? queries("VACUUM")
                    : apply_queries(
                        "VACUUM" . ($_POST["optimize"] ? "" : " ANALYZE"),
                        $_POST["tables"],
                    );
            $C = "Tables have been optimized.";
        } elseif (!$_POST["tables"]) {
            $C = "No tables.";
        } elseif (
            $J = queries(
                ($_POST["optimize"]
                    ? "OPTIMIZE"
                    : ($_POST["check"]
                        ? "CHECK"
                        : ($_POST["repair"]
                            ? "REPAIR"
                            : "ANALYZE"))) .
                    " TABLE " .
                    implode(
                        ", ",
                        array_map("Adminer\idf_escape", $_POST["tables"]),
                    ),
            )
        ) {
            while ($L = $J->fetch_assoc()) {
                $C .=
                    "<b>" .
                    h($L["Table"]) .
                    "</b>: " .
                    h($L["Msg_text"]) .
                    "<br>";
            }
        }
        queries_redirect(substr(ME, 0, -1), $C, $J);
    }
    page_header(
        $_GET["ns"] == ""
            ? "Database" . ": " . h(DB)
            : "Schema" . ": " . h($_GET["ns"]),
        $k,
        true,
    );
    if (adminer()->homepage()) {
        if ($_GET["ns"] !== "") {
            echo "<h3 id='tables-views'>" . "Tables and views" . "</h3>\n";
            $uh = tables_list();
            if (!$uh) {
                echo "<p class='message'>" . "No tables." . "\n";
            } else {
                echo "<form action='' method='post'>\n";
                if (support("table")) {
                    echo "<fieldset><legend>" .
                    "Search data in tables" .
                    " <span id='selected2'></span></legend><div>",
                        "<input type='search' name='query' value='" .
                            h($_POST["query"]) .
                            "'>",
                        script(
                            "qsl('input').onkeydown = partialArg(bodyKeydown, 'search');",
                            "",
                        ),
                        " <input type='submit' name='search' value='" .
                            "Search" .
                            "'>\n",
                        "</div></fieldset>\n";
                    if ($_POST["search"] && $_POST["query"] != "") {
                        $_GET["where"][0]["op"] = driver()->convertOperator(
                            "LIKE %%",
                        );
                        search_tables();
                    }
                }
                echo "<div class='scrollable'>\n",
                    "<table class='nowrap checkable odds'>\n",
                    script(
                        "mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});",
                    ),
                    '<thead><tr class="wrap">',
                    '<td><input id="check-all" type="checkbox" class="jsonly">' .
                        script(
                            "qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",
                            "",
                        ),
                    "<th>" . "Table",
                    "<td>" .
                        "Engine" .
                        doc_link(["sql" => "storage-engines.html"]),
                    "<td>" .
                        "Collation" .
                        doc_link([
                            "sql" => "charset-charsets.html",
                            "mariadb" =>
                                "supported-character-sets-and-collations/",
                        ]),
                    "<td>" .
                        "Data Length" .
                        doc_link(["sql" => "show-table-status.html"]),
                    "<td>" .
                        "Index Length" .
                        doc_link(["sql" => "show-table-status.html"]),
                    "<td>" .
                        "Data Free" .
                        doc_link(["sql" => "show-table-status.html"]),
                    "<td>" .
                        "Auto Increment" .
                        doc_link([
                            "sql" => "example-auto-increment.html",
                            "mariadb" => "auto_increment/",
                        ]),
                    "<td>" .
                        "Rows" .
                        doc_link(["sql" => "show-table-status.html"]),
                    support("comment")
                        ? "<td>" .
                            "Comment" .
                            doc_link(["sql" => "show-table-status.html"])
                        : "",
                    "</thead>\n";
                $T = 0;
                foreach ($uh as $D => $U) {
                    $si = $U !== null && !preg_match("~table|sequence~i", $U);
                    $u = h("Table-" . $D);
                    echo "<tr><td>" .
                    checkbox(
                        $si ? "views[]" : "tables[]",
                        $D,
                        in_array("$D", $vh, true),
                        "",
                        "",
                        "",
                        $u,
                    ),
                        "<th>" .
                            (support("table") || support("indexes")
                                ? "<a href='" .
                                    h(ME) .
                                    "table=" .
                                    urlencode($D) .
                                    "' title='" .
                                    "Show structure" .
                                    "' id='$u'>" .
                                    h($D) .
                                    "</a>"
                                : h($D));
                    if ($si) {
                        echo '<td colspan="6"><a href="' .
                        h(ME) .
                        "view=" .
                        urlencode($D) .
                        '" title="' .
                        "Alter view" .
                        '">' .
                        (preg_match("~materialized~i", $U)
                            ? "Materialized view"
                            : "View") .
                        "</a>",
                            '<td align="right"><a href="' .
                                h(ME) .
                                "select=" .
                                urlencode($D) .
                                '" title="' .
                                "Select data" .
                                '">?</a>';
                    } else {
                        foreach (
                            [
                                "Engine" => [],
                                "Collation" => [],
                                "Data_length" => ["create", "Alter table"],
                                "Index_length" => ["indexes", "Alter indexes"],
                                "Data_free" => ["edit", "New item"],
                                "Auto_increment" => [
                                    "auto_increment=1&create",
                                    "Alter table",
                                ],
                                "Rows" => ["select", "Select data"],
                            ]
                            as $y => $_
                        ) {
                            $u = " id='$y-" . h($D) . "'";
                            echo $_
                                ? "<td align='right'>" .
                                    (support("table") ||
                                    $y == "Rows" ||
                                    (support("indexes") && $y != "Data_length")
                                        ? "<a href='" .
                                            h(ME . "$_[0]=") .
                                            urlencode($D) .
                                            "'$u title='$_[1]'>?</a>"
                                        : "<span$u>?</span>")
                                : "<td id='$y-" . h($D) . "'>";
                        }
                        $T++;
                    }
                    echo support("comment")
                    ? "<td id='Comment-" . h($D) . "'>"
                    : "",
                        "\n";
                }
                echo "<tr><td><th>" . sprintf("%d in total", count($uh)),
                    "<td>" .
                        h(
                            JUSH == "sql"
                                ? get_val("SELECT @@default_storage_engine")
                                : "",
                        ),
                    "<td>" . h(db_collation(DB, collations()));
                foreach (["Data_length", "Index_length", "Data_free"] as $y) {
                    echo "<td align='right' id='sum-$y'>";
                }
                echo "\n", "</table>\n", "</div>\n";
                if (!information_schema(DB)) {
                    echo "<div class='footer'><div>\n";
                    $ni =
                        "<input type='submit' value='" .
                        "Vacuum" .
                        "'> " .
                        on_help("'VACUUM'");
                    $jf =
                        "<input type='submit' name='optimize' value='" .
                        "Optimize" .
                        "'> " .
                        on_help(
                            JUSH == "sql"
                                ? "'OPTIMIZE TABLE'"
                                : "'VACUUM OPTIMIZE'",
                        );
                    echo "<fieldset><legend>" .
                        "Selected" .
                        " <span id='selected'></span></legend><div>" .
                        (JUSH == "sqlite"
                            ? $ni .
                                "<input type='submit' name='check' value='" .
                                "Check" .
                                "'> " .
                                on_help("'PRAGMA integrity_check'")
                            : (JUSH == "pgsql"
                                ? $ni . $jf
                                : (JUSH == "sql"
                                    ? "<input type='submit' value='" .
                                        "Analyze" .
                                        "'> " .
                                        on_help("'ANALYZE TABLE'") .
                                        $jf .
                                        "<input type='submit' name='check' value='" .
                                        "Check" .
                                        "'> " .
                                        on_help("'CHECK TABLE'") .
                                        "<input type='submit' name='repair' value='" .
                                        "Repair" .
                                        "'> " .
                                        on_help("'REPAIR TABLE'")
                                    : ""))) .
                        "<input type='submit' name='truncate' value='" .
                        "Truncate" .
                        "'> " .
                        on_help(
                            JUSH == "sqlite"
                                ? "'DELETE'"
                                : "'TRUNCATE" .
                                    (JUSH == "pgsql" ? "'" : " TABLE'"),
                        ) .
                        confirm() .
                        "<input type='submit' name='drop' value='" .
                        "Drop" .
                        "'>" .
                        on_help("'DROP TABLE'") .
                        confirm() .
                        "\n";
                    $h = support("scheme")
                        ? adminer()->schemas()
                        : adminer()->databases();
                    if (count($h) != 1 && JUSH != "sqlite") {
                        $i = isset($_POST["target"])
                            ? $_POST["target"]
                            : (support("scheme")
                                ? $_GET["ns"]
                                : DB);
                        echo "<p><label>" . "Move to other database" . ": ",
                            $h
                                ? html_select("target", $h, $i)
                                : '<input name="target" value="' .
                                    h($i) .
                                    '" autocapitalize="off">',
                            "</label> <input type='submit' name='move' value='" .
                                "Move" .
                                "'>",
                            support("copy")
                                ? " <input type='submit' name='copy' value='" .
                                    "Copy" .
                                    "'> " .
                                    checkbox(
                                        "overwrite",
                                        1,
                                        $_POST["overwrite"],
                                        "overwrite",
                                    )
                                : "",
                            "\n";
                    }
                    echo "<input type='hidden' name='all' value=''>",
                        script(
                            "qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));" .
                                (support("table")
                                    ? " selectCount('selected2', formChecked(this, /^tables\[/) || $T);"
                                    : "") .
                                " }",
                        ),
                        input_token(),
                        "</div></fieldset>\n",
                        "</div></div>\n";
                }
                echo "</form>\n", script("tableCheck();");
            }
            echo "<p class='links'><a href='" .
            h(ME) .
            "create='>" .
            "Create table" .
            "</a>\n",
                support("view")
                    ? "<a href='" . h(ME) . "view='>" . "Create view" . "</a>\n"
                    : "";
            if (support("routine")) {
                echo "<h3 id='routines'>" . "Routines" . "</h3>\n";
                $_g = routines();
                if ($_g) {
                    echo "<table class='odds'>\n",
                        "<thead><tr><th>" .
                            "Name" .
                            "<td>" .
                            "Type" .
                            "<td>" .
                            "Return type" .
                            "<td></thead>\n";
                    foreach ($_g as $L) {
                        $D =
                            $L["SPECIFIC_NAME"] == $L["ROUTINE_NAME"]
                                ? ""
                                : "&name=" . urlencode($L["ROUTINE_NAME"]);
                        echo "<tr>",
                            '<th><a href="' .
                                h(
                                    ME .
                                        ($L["ROUTINE_TYPE"] != "PROCEDURE"
                                            ? "callf="
                                            : "call=") .
                                        urlencode($L["SPECIFIC_NAME"]) .
                                        $D,
                                ) .
                                '">' .
                                h($L["ROUTINE_NAME"]) .
                                "</a>",
                            "<td>" . h($L["ROUTINE_TYPE"]),
                            "<td>" . h($L["DTD_IDENTIFIER"]),
                            '<td><a href="' .
                                h(
                                    ME .
                                        ($L["ROUTINE_TYPE"] != "PROCEDURE"
                                            ? "function="
                                            : "procedure=") .
                                        urlencode($L["SPECIFIC_NAME"]) .
                                        $D,
                                ) .
                                '">' .
                                "Alter" .
                                "</a>";
                    }
                    echo "</table>\n";
                }
                echo '<p class="links">' .
                    (support("procedure")
                        ? '<a href="' .
                            h(ME) .
                            'procedure=">' .
                            "Create procedure" .
                            "</a>"
                        : "") .
                    '<a href="' .
                    h(ME) .
                    'function=">' .
                    "Create function" .
                    "</a>\n";
            }
            if (support("event")) {
                echo "<h3 id='events'>" . "Events" . "</h3>\n";
                $M = get_rows("SHOW EVENTS");
                if ($M) {
                    echo "<table>\n",
                        "<thead><tr><th>" .
                            "Name" .
                            "<td>" .
                            "Schedule" .
                            "<td>" .
                            "Start" .
                            "<td>" .
                            "End" .
                            "<td></thead>\n";
                    foreach ($M as $L) {
                        echo "<tr>",
                            "<th>" . h($L["Name"]),
                            "<td>" .
                                ($L["Execute at"]
                                    ? "At given time" .
                                        "<td>" .
                                        $L["Execute at"]
                                    : "Every" .
                                        " " .
                                        $L["Interval value"] .
                                        " " .
                                        $L["Interval field"] .
                                        "<td>$L[Starts]"),
                            "<td>$L[Ends]",
                            '<td><a href="' .
                                h(ME) .
                                "event=" .
                                urlencode($L["Name"]) .
                                '">' .
                                "Alter" .
                                "</a>";
                    }
                    echo "</table>\n";
                    $tc = get_val("SELECT @@event_scheduler");
                    if ($tc && $tc != "ON") {
                        echo "<p class='error'><code class='jush-sqlset'>event_scheduler</code>: " .
                            h($tc) .
                            "\n";
                    }
                }
                echo '<p class="links"><a href="' .
                    h(ME) .
                    'event=">' .
                    "Create event" .
                    "</a>\n";
            }
            if ($uh) {
                echo script("ajaxSetHtml('" . js_escape(ME) . "script=db');");
            }
        }
    }
}
page_footer();
