Explanation
-------------------------------
The smartshort template operator shortens input strings like the shorten
template operator but with a few important differences - most of the time
it will break at the end of a sentence and it will not break html tags.

The problem with the default shorten template operator is that it's a substring
of the input, so it will break wherever it will land... including in the middle
of a word.  Another problem is that if the input is an xml block with tags those
could end in the middle of a html tag and mess up the layout of the rest of
the page.  Or, with a long tag - which wouldn't be visible but would count in
the total for the sub string - would make the visible string too short.

Since practically all of our customers want minimal html tags in their line
views (see the strip_except template operator) - and want their line views
to all be shortened - it has always been problematic and up until now we've
only been able to allow self-closing tags or the content editors had to edit
the line view text block attribute carefully so that no html tags would be
stranded.  That didn't always happen.  Especially with user-generated content.

What smartshort will do is take the string and find the nearest end of
sentence punctuation and, if the length of the string (not including html tags)
at that point is less than the user-defined percentage of deviation,  break
the text block there.

If the length of the string at that point is more than the percentage of
deviation, then the string will be shortened to the white space nearest the
length set in the template.  if the length of the string at this point is
still bigger than the user-defined percentage of deviation, (which could happen
with a long run-on word and a short length) then it will break at the character
like the existing shorten.

The tags will be inserted but will not count toward the string length - since
they are hidden.  While calculating the length of the string, the
non-self-closing html tags are kept in an array, and if there are still open
tags when the string is to be displayed, those tags will be closed.  That list
of tags is user-defined.

Installation
-------------------------------

1) Put this extension's folder in the "extension" directory under the root of
   the ezp site.

2) Open the appropriate site.ini and add ActiveExtensions[]=smartshort (in the
   case of the override site.ini) or ActiveAccessExtension[]=smartshort to the
   [ExtensionSettings] block.

3) Usage in templates:
   {$attribute.content.output.output_text|smartshort([<int>],[<string>)}

   1st parameter: length to cut text block (default 80)
   2nd parameter: sequence to display at the end of the text block.

   More settings can be found in module.ini

   Specifically, a list of the tags that it will close if they are hanging and 
   the percentage of the length of the input string at which it will go to the
   next level down of where it will split.


Version history:
-------------------------------
* Release 1: initial release


Disclaimer & Copyright:
-------------------------------
/*
    smartshort extension for eZ publish 4.x
    Copyright (C) 2010  Steven E. Bailey

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/
