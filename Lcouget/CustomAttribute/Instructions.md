# Magento 2.0 Senior Developer Exercise

## Objective

<p>Create a custom Magento 2 module that adds a new product attribute and modifies the product detail page to display this attribute. Additionally, create a custom console command to update the values of this attribute for all products. Implement JavaScript validation for the custom attribute on the frontend and add feature toggle functionality to control the availability of the custom attribute based on different regions. Extend some of Magento's core functionality, such as a UI Component. </p>

## Requirements

### 1. Magento Setup:

- Set up a Magento 2 instance in your local environment.

- Provide detailed instructions on how to access the instance and test the module.

### 2. Module Creation:

- Create a custom module named `Vendor_CustomAttribute`.

- Register the module in the `app/code/Vendor/CustomAttribute` directory.

- Ensure the module is enabled and registered correctly in Magento.

### 3. Add Custom Product Attribute:

- Add a new product attribute called `custom_attribute` that is a text field.

- Ensure the attribute can be managed via the console command and displayed on the frontend product detail page.

### 4. Modify Product Detail Page:

- Modify the product detail page (`view/frontend/templates/product/view.phtml`) to display the `custom_attribute` value.

- Ensure the attribute is displayed in a user-friendly manner on the product detail page.

### 5. Create Custom Console Command:

- Create a custom console command that updates the `custom_attribute` value for all products.

- The command should take a single parameter (the new value for the attribute) and update all products with this value.

- Ensure the command is registered and executable via the Magento CLI.

### 6. Implement JavaScript Validation:

- Add a text input field for `custom_attribute` on the product detail page.

- Implement JavaScript validation to ensure the input value meets specific criteria (e.g., non-empty, minimum length of 3 characters).

- Display appropriate error messages if the validation fails.

### 7. Add Feature Toggle Functionality:

- Implement a feature toggle to enable or disable the `custom_attribute` functionality based on different regions or configurations.

- Add a configuration setting that can be managed via the console command to control the feature toggle.

- Ensure that when the feature is disabled, the custom attribute is not displayed on the product detail page or updated via the console command.

### 8. Extend Magento Core Functionality:

- Extend a Magento UI Component to integrate with the custom attribute.

- For example, extend the product listing UI Component to include the `custom_attribute` as a column.

- Ensure the extended UI Component is properly integrated and functional on the frontend.

## Deliverables
1. A GitHub repository containing the complete `Vendor_CustomAttribute` module.

2. Detailed instructions in the `README.md` file on how to install and test the module.

3. A brief explanation in the `README.md` file of the approach taken to solve each requirement.

4. Access details to the Magento instance where the module is set up and demonstrated. This can be a local environment with instructions.

## Evaluation Criteria
### 1. Code Quality:

- Clean, readable, and well-documented code.

- Proper use of Magento 2 coding standards and best practices.

### 2. Functionality:

- The Magento instance should be correctly set up and accessible.

- The module should work as described in the requirements.

- The custom attribute should be correctly added and displayed on the product detail page.

- The console command should update the attribute values correctly.

- JavaScript validation should work as expected and provide a good user experience.

- Feature toggle functionality should work correctly, enabling or disabling the custom attribute based on the configuration.

- The extended UI Component should be correctly integrated and functional on the frontend.

### 3. Innovation:

- Any additional features or improvements that enhance the module's functionality or usability.

## Submission Instructions

1. Create a public or private repository on GitHub.

2. Push your code to the repository.

3. Ensure the repository contains a `README.md` file with installation and testing instructions, and a brief explanation of your approach.

4. Provide access details to the Magento instance where the module is set up and demonstrated.

5. Submit the link to the repository and access details via email to [andred@ciandt.com] until July 8th 11am (or as soon as possible).

