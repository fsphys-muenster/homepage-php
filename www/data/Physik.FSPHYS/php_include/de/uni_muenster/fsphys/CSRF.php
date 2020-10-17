<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';
	
class CSRFException extends \UnexpectedValueException { }

class CSRF {
	const VALID_ORIGINS = [
		'https://sso.uni-muenster.de',
		'https://sso.uni-ms.de',
		'https://sso.wwu.de',
		'https://sso.wwu-muenster.de',
		'https://sso.xn--uni-mnster-eeb.de', // sso.uni-münster.de
		'https://sso.xn--wwu-mnster-eeb.de', // sso.wwu-münster.de
	];
	
	/**
	 * Indicates if request HTTP headers are valid (same/expected origin)
	 *
	 * @link https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet#Verifying_Same_Origin_with_Standard_Headers
	 * @return bool validity of the request headers. true if both ORIGIN and
	 *              REFERER are each valid or unset, false otherwise
	 * @see CSRF::VALID_ORIGINS
	 */
	static function http_headers_valid(): bool {
		$origin = $_SERVER['HTTP_ORIGIN'] ?? NULL;
		$referrer = $_SERVER['HTTP_REFERER'] ?? NULL;
		$origin_present = isset($origin);
		$origin_valid = !$origin_present || self::starts_with_one_of(
			$origin, self::VALID_ORIGINS);
		$referrer_present = isset($referrer);
		$referrer_valid = !$referrer_present || self::starts_with_one_of(
			$referrer, self::VALID_ORIGINS);
		// let validation pass if neither header is present, but log the
		// occurrence
		if (!$origin_present && !$referrer_present) {
			mail_and_log(new CSRFException('Neither ORIGIN nor REFERER HTTP '
				. 'headers were present.'));
			return true;
		}
		return $origin_valid && $referrer_valid;
	}
	
	static function check_http_headers(): void {
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '–';
		$referrer = $_SERVER['HTTP_REFERER'] ?? '–';
		if (!self::http_headers_valid()) {
			throw new CSRFException('CSRF validation failed – possible CSRF '
				. 'attack!' . "\n"
				. "ORIGIN  = $origin\n"
				. "REFERER = $referrer"
			);
		}
	}
	
	private static function starts_with_one_of(string $haystack,
		array $needles): bool {
		foreach ($needles as $needle) {
			if (Util::starts_with($haystack, $needle)) {
				return true;
			}
		}
		return false;
	}
}

