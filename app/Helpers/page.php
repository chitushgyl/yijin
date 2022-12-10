<?php
/**
 * @param 查询条件的处理
 */
function get_list_where($search){
    $where=[];
    $searchs=array_filter($search);

    //DUMP($searchs);
    foreach($searchs as $v){
        //先看value  是不是空的，如果是空的则不处理
        if($v['name']!==NULL && $v['value'] !==NULL){
            switch ($v['type']) {
				
                case 'like':
                    $where[] = [$v['name'],'like', '%'.$v['value'].'%'];
                    break;
					
                case 'all':
                    if($v['value']!='ALL' ){
                        $where[] = [$v['name'],'=', $v['value']];
                    }
                    break;
					
                default:
                    $where[] = [$v['name'],$v['type'], $v['value']];
                    break;
					
            }

        }

    }

    // dd($where);

    return $where;

}


?>
