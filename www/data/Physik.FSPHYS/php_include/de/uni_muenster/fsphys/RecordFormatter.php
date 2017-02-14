<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class RecordFormatter {
	protected $data;
	protected $locale;
	
	function __construct(MemberRecord $object, $locale=Localization::LOCALE) {
		$this->data = $object;
		$this->locale = $locale;
	}

	function attr(string $attr): string {
		return Util::htmlspecialchars($this->attr_raw($attr));
	}

	protected function attr_raw(string $attr): string {
		return $this->data->get_attr($attr, $this->locale) ?? '';
	}
}

