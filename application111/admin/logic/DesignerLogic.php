<?php
namespace ylt\admin\logic;

use think\Model;
use think\db;
use think\Url;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class DesignerLogic extends Model
{

    /**
     * 获得指定分类下的子分类的数组
     * @access  public
     * @param   int     $cat_id     分类的ID
     * @param   int     $selected   当前选中分类的ID
     * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int     $level      限定返回的级数。为0时返回所有级数
     * @return  mix
     */
    public function Works_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
    {
                global $Works_category, $Works_category2;
                $sql = "SELECT * FROM  __PREFIX__Works_category ORDER BY parent_id , sort_order ASC";
                $Works_category = DB::query($sql);
                $Works_category = convert_arr_key($Works_category, 'id');

                foreach ($Works_category AS $key => $value)
                {
                    if($value['level'] == 1)
                        $this->get_cat_tree($value['id']);
                }
                /*
                foreach ($Works_category2 AS $key => $value)
                {
                        $strpad_count = $value['level']*10;
                        echo str_pad('',$strpad_count,"-",STR_PAD_LEFT);
                        echo $value['name'];
                        echo "<br/>";
                }*/
                return $Works_category2;
    }

    /**
     * 获取指定id下的 所有分类
     * @global type $Works_category 所有商品分类
     * @param type $id 当前显示的 菜单id
     * @return 返回数组 Description
     */
    public function get_cat_tree($id)
    {
        global $Works_category, $Works_category2;
        $Works_category2[$id] = $Works_category[$id];
        foreach ($Works_category AS $key => $value){
             if($value['parent_id'] == $id)
             {
                $this->get_cat_tree($value['id']);
                $Works_category2[$id]['have_son'] = 1; // 还有下级
             }
        }
    }

     /**
     *  获取选中的下拉框
     * @param type $cat_id
     */
    function find_parent_cat($cat_id)
    {
        if($cat_id == null)
            return array();

        $cat_list =  Db::name('Works_category')->column('id,parent_id,level');
        $cat_level_arr[$cat_list[$cat_id]['level']] = $cat_id;

        // 找出他老爸
        $parent_id = $cat_list[$cat_id]['parent_id'];
        if($parent_id > 0)
             $cat_level_arr[$cat_list[$parent_id]['level']] = $parent_id;
        // 找出他爷爷
        $grandpa_id = $cat_list[$parent_id]['parent_id'];
        if($grandpa_id > 0)
             $cat_level_arr[$cat_list[$grandpa_id]['level']] = $grandpa_id;

        // 建议最多分 3级, 不要继续往下分太多级
        // 找出他祖父
        $grandfather_id = $cat_list[$grandpa_id]['parent_id'];
        if($grandfather_id > 0)
             $cat_level_arr[$cat_list[$grandfather_id]['level']] = $grandfather_id;

        return $cat_level_arr;
    }



}
