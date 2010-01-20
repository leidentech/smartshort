<?php
//
// Definition of eZTemplateSmartShortOperator class
//
// Created on: <19-Jan-2010 13:50:09 sbailey>
//
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.2.0
// 
// COPYRIGHT NOTICE: Copyright (C) 1999-2008 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
// This program is free software; you can redistribute it and/or
// modify it under the terms of version 2.0  of the GNU General
// Public License as published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of version 2.0 of the GNU General
// Public License along with this program; if not, write to the Free
// Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
// MA 02110-1301, USA.
//
//

/*!
  \class eZTemplateSmartShortOperator eztemplatesmartshortoperator.php
  \ingroup eZTemplateOperators
  \brief Display of string block  using operator "smartshorten".  Will shorten strings to length without breaking tags or leaving tags open.  It will also break the text on the nearest end punctuation and failing that (it isn't within 20% of the total string length) then it will try and break the string on the nearest whitespace.

  The operator can take two parameters. The first is the length to cut the string,
  the second is what the delimeter should be.

\code
// Example template code
input|smartshort( [ length [, sequence ] ] )

\endcode

*/
class SmartShortOperator
{
    /*!
     Initializes the object with the name $name, default is "smartshort".
    */
    function SmartShortOperator( $name = "smartshort" )
    {
        $this->AttributeName = $name;
        $this->Operators = array( $name );
    }

    /*!
     Returns the template operators.
    */
    function operatorList()
    {
        return array( 'smartshort' );
    }

    function operatorTemplateHints()
    {
        return array( $this->AttributeName => array( 'input' => true,
                                                     'output' => true,
                                                     'parameters' => 2 ) );
    }

    /*!
     See eZTemplateOperator::namedParameterList()
    */

    function namedParameterList()
    {
        return array( "length" =>	array( "type" => "integer",
                                               "required" => false,
                                               "default" => "80" ),
                      "sequence" =>	array( "type" => "string",
                                               "required" => false,
                                               "default" => "..." ) );
    }

    /*!
     Display the variable.
    */
    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
	$moduleINI = eZINI::instance( "module.ini" );
        $allowedDeviationSetting = $moduleINI->variable( "ModuleSettings", "AllowedDeviation" );
	if (!is_int($allowedDeviationSetting))
		$allowedDeviationSetting = 20;
        $length    = $namedParameters['length'];
        $sequence  = $namedParameters['sequence'];
        $trim_type = $namedParameters['trim_type'];
	$allowedDeviation = $allowedDeviationSetting / 100;

	$cleanString = strip_tags($operatorValue);

	if(strlen($cleanString) > $length) { //if it already fits we don't have to jump through any hoops to make it fit	
		$punctSplit = preg_match_all('/([.!?])/i', $cleanString,$punctMatches,PREG_OFFSET_CAPTURE);
		
		foreach($punctMatches[0] as $punctArray) { //Flatten the offset capture into an array
			$punctPositions[] = $punctArray[1];
		}

		$firstString=explode(" ",$cleanString);
		if ($length > strlen($firstString[0])) { //to check for really small length limits being passed
			if (!in_array($length,$punctPositions) OR count($punctPositions) == 0) { // don't have to do this if it already ended on puctuation or has no punctuation
				$current=$punctPositions[0];
				foreach($punctPositions as $position) {
					if (abs($length - $position) < abs($length - $current) ) {
						$current = $position;
					}
					if ($position >= $length) {
						break;
					}
				}
			}

			if ( $length * $allowedDeviation >= abs($length - $current)) {
				$length=$current+1;
			} else { //deviation is greater than allowed deviation - let's split on whitespace instead.
				$spaceSplit = preg_match_all('%(\w+)%uxis', $cleanString,$spaceMatches,PREG_OFFSET_CAPTURE);
				foreach($spaceMatches[0] as $spaceArray) { //Flatten the offset capture into an array
					$spacePositions[] = $spaceArray[1];
				}
				if (!in_array($length,$spacePositions) OR count($spacePositions) == 0) { // don't have to do this if it already ended on a space
					$current=$spacePositions[0];
					foreach($spacePositions as $position) {
						if (abs($length - $position) < abs($length - $current) ) {
							$current = $position-1;
						}
						if ($position >= $length) {
							break;
						}
					}
					$length=$current;
				} elseif(in_array($length,$spacePositions)) {
					$length=$length-1; //get rid of self then since it's whitespace
				}
			}
		} else { //$length is less than the first string.
			$current = strlen($firstString[0]);
			if ( $length * $allowedDeviation >= abs($length - $current)) { //deviation is less than allowed deviation so go with the first string.
				$length = $current;
			} //else we split on the character - which is already what $length is set to.
		}
	}
	//end length calculation.

	if ($operatorValue == $cleanString) {  //No tags, just dump the sub string and we're done weehaw.
		$output = substr( $cleanString, 0, $length );
		if($spacePositions) { // Don't need sequence if it ended on a sentence in my humble opinion.
			$output = $output.$sequence;
		}
	} else { //has tags, uh oh
		$tagSplit = preg_split('%(</?[\w][^>]*>)%uxis',$operatorValue,-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		$badTagArray=$moduleINI->variable( "ModuleSettings", "HangingTags" );

		foreach($tagSplit as $tagChunk) {
			if (preg_match('%(<[\w]+[^>]*>.*?)%uxis', $tagChunk,$tagMatches) == 1) { //open tag
			//if it's a tag don't count it toward the string length since it's hidden, but do try and figure out if it's a non-self-closing tag that we'll have to find and close
				$tag=explode(" ",$tagMatches[0]);
				$tag=rtrim($tag[0]," >");
				if (count(preg_grep("%^$tag$%uxis", $badTagArray)) > 0){
					$openTagArray[]=$tag;
				}
			} elseif (preg_match('%(<\/[\w]+[^>]*>.*?)%uxis', $tagChunk,$tagMatches) == 1) { //close tag
				if(count($openTagArray) > 0) // better not do this unless array has content
					array_pop($openTagArray);
			} else {
				$outputlength = $outputlength + strlen($tagChunk);
			}

			if ($outputlength >= $length) {
				$output = $output.substr( $tagChunk, 0, $length - $prevoutputlength );
				if(count($openTagArray) != 0 ) {
				//need to check if the tag needs to be closed
					foreach(array_reverse($openTagArray) as $openTag) {
						switch ($openTag) {
						default:
							//convert <tag to </tag>
							$openTag = ltrim($openTag,"<");
							$openTag ="</".$openTag.">";
							$output = $output.$openTag;
							break;
						}
					}
				}
				//need to check if the tag needs to be closed
				break;
			}
			$prevoutputlength = $outputlength;
			$output = $output.$tagChunk;
		}
	}
	
	$operatorValue=$output;
	return;
    }

    /// The array of operators, used for registering operators
    public $Operators;
}

?>
