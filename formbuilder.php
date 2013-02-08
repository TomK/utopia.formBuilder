<?php
// include default styles
uCSS::IncludeFile(dirname(__FILE__).'/formbuilder.css');

class formBuilder_Forms extends uTableDef {
	function SetupFields() {
		$this->AddField('form_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);
		$this->AddField('recipient',ftVARCHAR,255);
		
		$this->AddField('form_header',ftTEXT);
		
		$this->AddField('success_redirect',ftVARCHAR,255);
		$this->AddField('screen_response',ftTEXT);
		
		$this->AddField('email_response_subject',ftVARCHAR,250);
		$this->AddField('email_response',ftTEXT);
		
		$this->SetPrimaryKey('form_id');
	}
}
class formBuilderAdmin_Forms extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Form Builder'; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_DELETE | ALLOW_EDIT; }
	public function GetTableDef() { return 'formBuilder_Forms'; }
	public function SetupParents() {
		$this->AddParent('/');
	}
	public function SetupFields() {
		$this->CreateTable('forms');
		$this->AddField('name','name','forms','Form Name');
		$this->AddField('recipient','recipient','forms','Recipient');
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'name') $newValue = UrlReadable($newValue);
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
	public function RunModule() { $this->ShowData(); }
}
class formBuilderAdmin_FormsDetail extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'Edit Form'; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }
	public function GetTableDef() { return 'formBuilder_Forms'; }
	public function SetupParents() {
		$this->AddParent('formBuilderAdmin_Forms','form_id','*');
	}
	public function SetupFields() {
		$this->CreateTable('forms');
		$this->AddField('name','name','forms','Form Name',itTEXT);
		$this->AddField('recipient','recipient','forms','Form Recipient',itTEXT);
		$this->AddField('form_header','form_header','forms','Form Header',itTEXT);
		$this->AddSpacer();
		$this->AddField('success_redirect','success_redirect','forms','Redirect on Success',itTEXT);
		$this->AddField('screen_response','screen_response','forms','Successful Response (on screen)',itRICHTEXT);
		$this->NewSection('Email Response');
		$this->AddField('email_response_subject','email_response_subject','forms','Subject',itTEXT);
		$this->AddField('email_response','email_response','forms','Content',itHTML);
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'name') $newValue = UrlReadable($newValue);
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
	public function RunModule() { $this->ShowData(); }
	public function showForm($oVal, $pk) {
		$o=utopia::GetInstance('formBuilder_ShowForm');
		return $o->ShowForm(intval($pk));
	}
}


class formBuilder_Fields extends uTableDef {
	function SetupFields() {
		$this->AddField('field_id',ftNUMBER);
		$this->AddField('form_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);
		$this->AddField('type',ftVARCHAR,50);
		$this->AddField('default',ftTEXT);
		$this->AddField('values',ftTEXT);
		$this->AddField('required',ftBOOL);
		$this->AddField('email',ftBOOL);
		$this->AddField('validation',ftVARCHAR,100);
		
		$this->SetFieldProperty('type','default',itTEXT);
		
		$this->SetPrimaryKey('field_id');
		$this->SetIndexField('form_id');
	}
}
class formBuilderAdmin_Fields extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Form Fields'; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }
	public function GetTableDef() { return 'formBuilder_Fields'; }
	public function SetupParents() {
		$this->AddParent('formBuilderAdmin_FormsDetail','form_id','');
		uEvents::AddCallback('AfterRunModule',array(utopia::GetInstance('formBuilderAdmin_Fields'),'RunModule'),'formBuilderAdmin_FormsDetail');
	}
	public function SetupFields() {
		$this->CreateTable('fields');
		$this->CreateTable('form','formBuilder_Forms','fields','form_id');
		$this->AddField('form_id','form_id','fields');
		$this->AddField('form_name','name','form','Form');
		
		$this->AddField('name','name','fields','Name',itTEXT);
		$this->AddField('type','type','fields','Type',itCOMBO,array(itNONE=>'Text only',itTEXT=>'Text Box',itTEXTAREA=>'Multiline Text',itCOMBO=>'Dropdown',itCHECKBOX=>'Checkbox',itFILE=>'File Upload'));
		$this->AddField('default','default','fields','Default',itTEXT);
		
		// validation
		$this->AddField('required','required','fields','Required',itCHECKBOX);
		$this->AddField('email','email','fields','Email',itCHECKBOX);
		$this->AddField('validation','validation','fields','Validation (Regex)',itTEXT);
		
		$this->AddField('values','values','fields','Values',itTEXTAREA);
		
		$this->AddFilter('form_id',ctEQ,itNONE);
		$this->AddFilter('form_name',ctEQ,itNONE);
	}
	public function RunModule() {
		$fltr = $this->FindFilter('form_id');
		if (!$this->GetFilterValue($fltr['uid'])) return;
		$this->ShowData();
	}
}


class formBuilder_Submissions extends uTableDef {
	function SetupFields() {
		$this->AddField('submission_id',ftNUMBER);
		$this->AddField('form_id',ftNUMBER);
		$this->AddInputDate('date');
		$this->SetPrimaryKey('submission_id');
		$this->SetIndexField('form_id');
	}
}
class formBuilder_SubmissionData extends uTableDef {
	function SetupFields() {
		$this->AddField('data_id',ftNUMBER);
		$this->AddField('submission_id',ftNUMBER);
		$this->AddField('field',ftVARCHAR,50);
		$this->AddField('value',ftFILE);
		$this->SetPrimaryKey('data_id');
		$this->SetIndexField('submission_id');
	}
}
utopia::AddTemplateParser('form',array(utopia::GetInstance('formBuilder_ShowForm'),'ShowForm'));
class formBuilder_ShowForm extends uDataModule {
	public function GetOptions() { return ALLOW_FILTER | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }
	public function GetTableDef() { return 'formBuilder_SubmissionData'; }
	public function SetupParents() {
	}
	public function SetupFields() {
		$this->CreateTable('subdata');
		
		$this->AddField('submission_id','submission_id','subdata');
		$this->AddField('field','field','subdata');
		$this->AddField('value','value','subdata');
	}
	public function RunModule() { utopia::PageNotFound(); }
	public function ShowForm($id) {
		$obj = utopia::GetInstance('formBuilderAdmin_FormsDetail');
		
		$form = $obj->LookupRecord(array('form_id'=>$id),true);
		if (!$form) $form = $obj->LookupRecord(array('name'=>$id),true);
		if (!$form) return 'No Form Found';
		$id = $form['form_id'];
		
		$obj = utopia::GetInstance('formBuilderAdmin_Fields');
		$fields = $obj->GetDataset(array('form_id'=>$id))->fetchAll();
		if (!$fields) return 'No Fields Found';
		
		do if (isset($_POST['form_id']) && $_POST['form_id'] == $id) {
			// validation
			$verified = true;
			foreach ($fields as $k=> $field) {
				if (!$field['type']) continue;
				if (!$field['required'] && (!isset($_POST['fb-field-'.$field['field_id']]) || $_POST['fb-field-'.$field['field_id']] == '')) continue;
				
				// verify form fields, add [error]s if needed
				if ($field['email'] && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$_POST['fb-field-'.$field['field_id']])) {
					$fields[$k]['error'] = 'You must enter a valid email address';
					$verified = false; continue;
				}
				if ($field['validation'] && !preg_match('/'.$field['validation'].'/i',$_POST['fb-field-'.$field['field_id']])) {
					$fields[$k]['error'] = 'This field does not match the required format.';
					$verified = false; continue;
				}
				// required?
				if ($field['required'] && ((!isset($_POST['fb-field-'.$field['field_id']]) || !$_POST['fb-field-'.$field['field_id']]) && (!isset($_FILES['fb-field-'.$field['field_id']]) || !$_FILES['fb-field-'.$field['field_id']]['tmp_name']))) {
					$fields[$k]['error'] = 'This field is required.';
					$verified = false; continue;
				}
			}
			// break if not verified
			if (!$verified) break;
			
			// set up submission table
			$o = utopia::GetInstance('formBuilder_Submissions');
			$subPk = NULL;
			$o->UpdateField('form_id',$form['form_id'],$subPk);
			
			$attachments = array();
			foreach ($fields as $field) {
				$dPk = NULL;
				$this->UpdateFields(array(
					'submission_id'	=> $subPk,
					'field'			=> $field['field_id'],
				),$dPk);
				// add to database
				if ($field['type'] === itFILE) {
					if (!isset($_FILES['fb-field-'.$field['field_id']]) || !$_FILES['fb-field-'.$field['field_id']]['tmp_name']) continue;
					$this->UploadFile('value',$_FILES['fb-field-'.$field['field_id']],$dPk);
					$attachments[] = Swift_Attachment::newInstance(file_get_contents($_FILES['fb-field-'.$field['field_id']]['tmp_name']), $_FILES['fb-field-'.$field['field_id']]['name'], $_FILES['fb-field-'.$field['field_id']]['type']);
					continue;
				}
				$this->UpdateField('value',$_POST['fb-field-'.$field['field_id']],$dPk);
			}
			
			// format email
			$emailResponse = null;
			$emailContent = 'A user has submitted a form: '.$form['name']."\n\n";
			foreach ($fields as $field) {
				if (!$field['type']) continue;
				$emailContent .= $field['name'].': ';
				if ($field['type'] === itFILE && (isset($_FILES['fb-field-'.$field['field_id']]) && $_FILES['fb-field-'.$field['field_id']]['tmp_name'])) $emailContent .= $_FILES['fb-field-'.$field['field_id']]['name'].' (Attached)';
				elseif (isset($_POST['fb-field-'.$field['field_id']])) {
					if ($field['values']) {
						$fv = explode(PHP_EOL,$field['values']);
						$val = $_POST['fb-field-'.$field['field_id']];
						if (!is_array($val)) $val = array($val);
						$vals = array();
						foreach ($val as $v) {
							$vals[] = $fv[$v];
						}
						$emailContent .= implode(', ',$vals);
					} else $emailContent .= $_POST['fb-field-'.$field['field_id']];
				}
				$emailContent .= "\n";
				if ($field['email'] && !$emailResponse && isset($_POST['fb-field-'.$field['field_id']])) $emailResponse = $_POST['fb-field-'.$field['field_id']];
			}
			
			// send emails
			uEmailer::SendEmail($form['recipient'],'Form Completion: '.$form['name'],$emailContent,NULL,$attachments);
			if ($emailResponse && $form['email_response_subject'] && $form['email_response'])
				uEmailer::SendEmail($emailResponse,$form['email_response_subject'],$form['email_response']);
			
			if ($form['success_redirect']) {
				header('Location: '.$form['success_redirect']);
			}
			
			return $form['screen_response']?$form['screen_response']:'';
		} while (false);
		$output = '<div class="fb-form fb-form-'.$form['form_id'].' fb-form-'.$form['name'].'">';
		if (isset($form['form_header'])) $output .= '<div class="fb-head">'.$this->GetCell('form_header',$form).'</div>';
		$output .= '<form action="" method="post" enctype="multipart/form-data">';
		$output .= '<input type="hidden" name="form_id" value="'.$id.'">';
		$output .= '<div class="fb-fields">';
		foreach ($fields as $field) {
			//if (!$field['type']) continue;
			$output .= '<div class="fb-fieldset">';
			$default = $field['default'];
			if (isset($_POST['fb-field-'.$field['field_id']])) $default = $_POST['fb-field-'.$field['field_id']];
			$vals = $field['values']; if ($vals) $vals = explode(PHP_EOL,$field['values']);
			$output .= '<span class="fb-fieldname">'.$field['name'].'</span>'.utopia::DrawInput('fb-field-'.$field['field_id'],$field['type'],$default,$vals,array('class'=>'fb-field','placeholder'=>$field['name']));
			// any error?
			if (isset($field['error'])) // uNotices::AddNotice($field['error'],NOTICE_TYPE_ERROR);
				$output .= '<span class="fb-error">'.$field['error'].'</span>';
			$output .= '</div>';
		}
		$output .= '<div class="fb-fieldset">';
		$output .= '<input class="fb-submit" type="submit" value="Send">';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</form></div>';
		return $output;
	}
}


class formBuilderAdmin_Submissions extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Submissions'; }
	public function GetOptions() { return null; }
	public function GetTableDef() { return 'formBuilder_Submissions'; }
	public function SetupParents() {
		$this->AddParent('formBuilderAdmin_FormsDetail','form_id');
	}
	public function SetupFields() {
		$fltr = $this->FindFilter('form_id');
		$formid = $this->GetFilterValue($fltr['uid']);
		
		$this->CreateTable('sub');
		$this->CreateTable('data','formBuilder_SubmissionData','sub','submission_id');
		$this->AddField('submission_date','date','sub','Submitted');

		$o = utopia::GetInstance('formBuilderAdmin_Fields');
		$ds = $o->GetDataset();
		while (($row = $ds->fetch())) {
			$this->AddField('field_'.$row['field_id'],'(MAX( IF( {field} = \''.$row['name'].'\' OR {field} = \''.$row['field_id'].'\', {value}, NULL) ))','data',$row['name']);
			if ($row['type'] === itFILE) {
				$this->AddField('field_'.$row['field_id'].'_filename','(MAX( IF( {field} = \''.$row['name'].'\' OR {field} = \''.$row['field_id'].'\', {value_filename}, NULL) ))','data');
				$this->AddField('field_'.$row['field_id'].'_filetype','(MAX( IF( {field} = \''.$row['name'].'\' OR {field} = \''.$row['field_id'].'\', {value_filetype}, NULL) ))','data');
				$this->SetFieldType('field_'.$row['field_id'],ftFILE);
			}
		}
		$this->AddOrderBy('submission_date');
		$this->AddGrouping('submission_id');
		
	}
	public function RunModule() { $this->ShowData(); }
}

uEvents::AddCallback('AfterRunModule',array(utopia::GetInstance('formBuilderAdmin_Submissions'),'RunModule'),'formBuilderAdmin_FormsDetail');

