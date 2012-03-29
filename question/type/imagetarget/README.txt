Image Target Question
--------------------

Author: Adriane Boyd (adrianeboyd@gmail.com)

Description:

The image target question asks students to select a particular area of an 
image.  The javascript version has a draggable bull's eye that the student 
drags onto the image.  The non-javascript version has the student click on 
the image to submit a response.

The teacher interface contains a drop-down menu of all the images uploaded 
in the course.  After selecting one, clicking on "Insert image to specify 
answer" will update the question editing page with a copy of the image 
where the teacher can select the area of the question that corresponds to 
the correct answer.  Additional areas in the image can be selected by 
clicking "Add another target area".  The teacher can provide feedback for 
a correct response and feedback for an incorrect response.

Warning about non-javascript fallback version:

+ In adaptive mode, the non-javascript version is only guaranteed to be 
  graded correctly if it is the only question on a page. (Penalties may be 
  incorrectly applied when other questions are submitted.)

Grading:

The response is taken from the center of the bull's eye image or from the 
point where the student clicked.  The response is either completely 
correct (within the specified area in the image) or completely incorrect 
(outside the specified area).

Other notes:

You may replace bullseye.png with any bull's eye image that you prefer.

The teacher interface uses the Javascript Image Cropper UI, available 
here:

http://www.defusion.org.uk/code/javascript-image-cropper-ui-using-prototype-scriptaculous/

See the cropper/ directory for the license.

