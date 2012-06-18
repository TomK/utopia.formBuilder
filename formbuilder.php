<?php
// include default styles
uCSS::IncludeFile(dirname(__FILE__).'/formbuilder.css');

class formBuilder_Forms extends uTableDef {
	function SetupFields() {
		$this->AddField('form_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);
		$this->AddField('recipient',ftVARCHAR,50);
		
		$this->AddField('form_header',ftTEXT);
		
		$this->AddField('screen_response',ftTEXT);
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
		$this->AddField('form_header','form_header','forms','Form Header',itTEXT);
		$this->AddField('screen_response','screen_response','forms','Successful Response (on screen)',itTEXT);
		$this->AddSpacer();
		$this->AddField('formshow',array($this,'showForm'),'','Preview');
		$this->NewSection('Emails');
		$this->AddField('recipient','recipient','forms','Form Recipient',itTEXT);
		$this->AddField('email_response','email_response','forms','Email Response',itTEXTAREA);
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
		$this->AddField('default',ftVARCHAR,50);
		$this->AddField('values',ftVARCHAR,50);
		
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
		$this->AddField('type','type','fields','Type',itCOMBO,array('Disable'=>itNONE,'Text Box'=>itTEXT,'Multiline Text'=>itTEXTAREA,'Email'=>'email','File Upload'=>itFILE));
		$this->AddField('default','default','fields','default',itTEXT);
		$this->AddField('values','values','fields','values',itTEXT);
		
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
		$this->AddField('value',ftLONGTEXT);
		$this->SetPrimaryKey('data_id');
		$this->SetIndexField('submission_id');
	}
}
utopia::AddTemplateParser('form',array(utopia::GetInstance('formBuilder_ShowForm'),'ShowForm'));
class formBuilder_ShowForm extends uDataModule {
	public function GetTitle() { return 'Form Fields'; }
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
		if (!is_numeric($id)) {
			$form = $obj->LookupRecord(array('form_name'=>$id),true);
		} else {
			$form = $obj->LookupRecord(array('form_id'=>$id),true);
		}
		
		if (!$form) return 'No Form Found';
		$id = $form['form_id'];
		
		$obj = utopia::GetInstance('formBuilderAdmin_Fields');
		$fields = $obj->GetRows(array('form_id'=>$id));
		if (!$fields) return 'No Fields Found';
		
		do if (isset($_POST['form_id']) && $_POST['form_id'] == $id) {
			$verified = true;
			foreach ($fields as $k=> $field) {
				// verify form fields, add [error]s if needed
				if ($field['type'] == 'email' && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$_POST['fb-field-'.$field['field_id']])) {
					$fields[$k]['error'] = 'You must enter a valid email address';
					$verified = false;
				}
				// required?
			}
			// break if not verified
			if (!$verified) break;
			
			// set up submission table
			$o = utopia::GetInstance('formBuilder_Submissions');
			$subPk = NULL;
			$o->UpdateField('form_id',$form['form_id'],$subPk);
			
			foreach ($fields as $field) {
				$dPk = NULL;
				$this->UpdateFields(array(
					'submission_id'	=> $subPk,
					'field'			=> $field['name'],
				),$dPk);
				// add to database
				if ($field['type']==itFILE && isset($_FILES['fb-field-'.$field['field_id']])) {
					$this->UploadFile('value',$_FILES['fb-field-'.$field['field_id']],$dPk);
					continue;
				}
				$this->UpdateField('value',$_POST['fb-field-'.$field['field_id']],$dPk);
			}
			
			// send emails
			uEmailer::SendEmail($form['recipient'],'Form Completion: '.$form['name'],var_export($_POST,true));
			return $form['screen_response']?$form['screen_response']:'';
		} while (false);
		
		$output = '<div class="fb-form fb-form-'.$form['form_id'].' fb-form-'.$form['name'].'">';
		if (isset($form['form_header'])) $output .= '<div class="fb-head">'.$form['form_header'].'</div>';
		$output .= '<form action="" method="post" enctype="multipart/form-data">';
		$output .= '<input type="hidden" name="form_id" value="'.$id.'">';
		$output .= '<div class="fb-fields">';
		foreach ($fields as $field) {
			if (!$field['type']) continue;
			$output .= '<div class="fb-fieldset">';
			if ($field['type'] == 'email') $field['type'] = itTEXT;
			$default = isset($_POST['fb-field-'.$field['field_id']]) ? $_POST['fb-field-'.$field['field_id']] : isset($field['default'])?$field['default']:'';
			$output .= '<span class="fb-fieldname">'.$field['name'].'</span>'.utopia::DrawInput('fb-field-'.$field['field_id'],$field['type'],$default,$field['values'],array('class'=>'fb-field'));
			// any error?
			if (isset($field['error'])) uNotices::AddNotice($field['error'],NOTICE_TYPE_ERROR);
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '<input class="fb-submit" type="submit">';
		$output .= '</form></div>';
		return $output;
	}
}



class formBuilderAdmin_Submissions extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Form Fields'; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_DELETE; }
	public function GetTableDef() { return 'formBuilder_Fields'; }
	public function SetupParents() {
		$this->AddParent('formBuilderAdmin_Forms','form_id');
		$this->AddParent('formBuilderAdmin_Forms','form_id','edit_fields');
	}
	public function SetupFields() {
		$this->CreateTable('fields');
		$this->CreateTable('form','formBuilder_Forms','fields','form_id','JOIN');
		$this->AddField('form_id','form_id','fields');
		$this->AddField('form_name','name','form','Form');
		
		$this->AddField('name','name','fields','Name',itTEXT);
		$this->AddField('type','type','fields','Type',itCOMBO,array('Disable'=>itNONE,'Text Box'=>itTEXT,'Multiline Text'=>itTEXTAREA));
		$this->AddField('default','default','fields','default',itTEXT);
		$this->AddField('values','values','fields','values',itTEXT);
	}
	public function RunModule() { $this->ShowData(); }
}


// view submissions

