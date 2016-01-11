<?php
/**
 * Allows shop members to update their details with the shop.
 *
 * @package shop
 * @subpackage forms
 */
class ShopAccountForm extends Form {

	public function __construct($controller, $name) {
		$member = Member::currentUser();
		$requiredFields = null;
		if($member && $member->exists()) {
			$fields = $member->getMemberFormFields();
			$fields->removeByName('Password');
			$requiredFields = $member->getValidator();
			$requiredFields->addRequiredField('Surname');
		} else {
			$fields = new FieldList();
		}
		if(get_class($controller) == 'AccountPage_Controller'){
			$actions = new FieldList(new FormAction('submit', _t('MemberForm.Save', 'Save Changes')));
		}
		else{
			$actions = new FieldList(
				new FormAction('submit', _t('MemberForm.Save', 'Save Changes')),
				new FormAction('proceed', _t('MemberForm.SaveAndProceed', 'Save and proceed to checkout'))
			);
		}
		parent::__construct($controller, $name, $fields, $actions, $requiredFields);
		if($member){
			$member->Password = ""; //prevents password field from being populated with encrypted password data
			$this->loadDataFrom($member);
		}
		if($record = $controller->data()){
			$record->extend('updateShopAccountForm', $fields, $actions, $requiredFields);
		}
	}

	/**
	 * Save the changes to the form
	 */
	public function submit($data, $form, $request) {
		$member = Member::currentUser();
		if(!$member) return false;

		$form->saveInto($member);
		$member->write();
		$form->sessionMessage(_t("MemberForm.DetailsSaved", 'Your details have been saved'), 'good');

		Controller::curr()->redirectBack();
		return true;
	}

	/**
	 * Save the changes to the form, and redirect to the checkout page
	 */
	public function proceed($data, $form, $request) {
		$member = Member::currentUser();
		if(!$member) return false;
		$form->saveInto($member);
		$member->write();
		$form->sessionMessage(_t("MemberForm.DetailsSaved", 'Your details have been saved'), 'good');
		Controller::curr()->redirect(CheckoutPage::find_link());
		return true;
	}

}

/**
* Validates the shop account form.
* @subpackage forms
*/
class ShopAccountFormValidator extends RequiredFields{

	/**
	 * Ensures member unique id stays unique.
	 */
	public function php($data) {
		$valid = parent::php($data);
		$field = Member::get_unique_identifier_field();
		if(isset($data[$field])){
			$uid = $data[Member::get_unique_identifier_field()];
			$currentmember = Member::currentUser();
			//can't be taken
			if(DataObject::get_one('Member', "$field = '$uid' AND ID != ".$currentmember->ID)){
				// get localized field labels
				$fieldLabels = $currentmember->fieldLabels(false);
				// if a localized value exists, use this for our error-message
				$fieldLabel = isset($fieldLabels[$field]) ? $fieldLabels[$field] : $field;

				$this->validationError(
					$field,
					// re-use the message from checkout
                    _t(
                        'Checkout.MemberExists',
                        'A member already exists with the {Field} {Identifier}',
                        '',
                        array('Field' => $fieldLabel, 'Identifier' => $uid)
                    ),
					"required"
				);
				$valid = false;
			}
		}
		return $valid;
	}

}
