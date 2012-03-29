/**
 * Javascript for image target question.
 *
 * @copyright &copy; 2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_imagetarget
 */

(function() {

var Dom = YAHOO.util.Dom;

// This item is will extend YAHOO.util.DD

MoodleImageClickItem = function(id, sGroup, config) {
    MoodleImageClickItem.superclass.constructor.call(this, id, sGroup, config);

    var el = this.getDragEl();

    this.sGroup = sGroup;
    this.isTarget = false;
};

YAHOO.extend(MoodleImageClickItem, YAHOO.util.DD, {

    onInvalidDrop: function(e, id) {
        // return the bull's eye to its original position on an invalid drop
        var el = this.getEl();
        var parent = Dom.get(el.parentNode.id);
        Dom.setXY(el, Dom.getXY(parent));
    }
});

})();


// Set the hidden response variable to the center of the position of
// the bull's eye
function ddImageClickSetHiddens(event, vars) {
    var id = vars.id;

    var Dom = YAHOO.util.Dom;

    var image = Dom.get(id + "image");
    var target = Dom.get(id + "target");
    var hidden = Dom.get("hidden" + id);

    targetregion = Dom.getRegion(target);
    targetwidth = targetregion.right - targetregion.left;
    targetheight = targetregion.bottom - targetregion.top;
    xpos = Dom.getX(target) - Dom.getX(image) + ((targetwidth / 2) | 0);
    ypos = Dom.getY(target) - Dom.getY(image) + ((targetheight / 2) | 0);

    if (xpos > 0 && ypos > 0) {
        hidden.value = xpos + ',' + ypos;
    } else {
        hidden.value = "0,0";
    }
}
