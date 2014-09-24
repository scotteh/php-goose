#PHP Goose - Article Extractor

##Intro

PHP Goose is a port of [Goose](https://github.com/GravityLabs/goose/) originally developed in Java and converted to Scala by [GravityLabs](https://github.com/GravityLabs/). Portions have also been ported from the Python port [python-goose](https://github.com/grangier/python-goose). It's mission is to take any news article or article type web page and not only extract what is the main body of the article but also all meta data and most probable image candidate.

The extraction goal is to try and get the purest extraction from the beginning of the article for servicing flipboard/pulse type applications that need to show the first snippet of a web article along with an image.

Goose will try to extract the following information:

 - Main text of an article
 - Main image of article
 - Any Youtube/Vimeo movies embedded in article
 - Meta Description
 - Meta tags
 - Publish Date
 
The php version was rewritten by:

 - Andrew Scott

##Licensing

PHP Goose is licensed by Gravity.com under the Apache 2.0 license, see the LICENSE file for more details
