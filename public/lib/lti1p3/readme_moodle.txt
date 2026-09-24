LTI 1.3 Tool Library import instructions

This library is a patched for use in Moodle - it requires the following changes be applied on top of the packback upstream base:
1. Removal of phpseclib dependency in src/JwksEndpoint.php (replaces a single call with openssl
equivalent) - see https://github.com/snake/lti-1-3-php-library/commit/08acb3962b7b19e207dbe0a651c33252261caeea

To upgrade to a new version of this library:
1. Clone the latest version of the upstream library from github:
https://github.com/packbackbooks/lti-1-3-php-library/tags
2. Apply the change mentioned above on top of the upstream clone.
3. Replace lib/lti1p3/src/ with the library's /src directory
4. Copy LICENSE.md to lib/lti1p3/
5. Copy README.md to lib/lti1p3/
6. Copy composer.json to lib/lti1p3/
7. Update the library entry in lib/thirdpartylibs.xml
8. Update the dependency note in lib/php-jwt/readme_moodle.txt, recording the version of php-jwt lib/lti1p3 depends on, if needed.
9. Check the upstream library's release notes and UPGRADES.md for any backwards incompatible changes to names, etc.
Moodle's calling code may require updates if changes are breaking -  so check this and make any changes if needed.
10. Run all unit tests in enrol/lti and auth/lti.
11. Regression test Moodle-to-Moodle LTI using LTI Advantage (not legacy) using the relevant MDLQA tests as a guide.

Note: the MDL-87789 lineitem fix previously patched here was fixed upstream in
v6.4.1 and was removed as redundant during the MDL-89600 upgrade to 6.4.4.
