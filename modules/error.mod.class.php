<?php
## this module is used only for displaying errors ;D
class error extends vengine_mod
{
  public function engine()
  {
		$this->display_interface('error');
		return $this->output;
  }
}
?>