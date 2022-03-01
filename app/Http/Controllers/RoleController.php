<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleAccess;
use App\Models\WebMenu;
use App\Services\Response;
use DB;
use App\Constants\Constant;

class RoleController extends Controller
{

    /**
     * Get Role
     *
     * @return [json] Role object
     */
    public function list()
    {
        $roles = Role::all();
        return Response::success($roles);
    }

    /**
     * Get Role
     *
     * @return [json] Role object
     */
    public function access()
    {

        $menusByRole = RoleAccess::
                leftJoin('web_menu', 'web_menu.idmenu', '=', 'role_access.idmenu')
                ->where('parent_idmenu', null)
                ->where('role_access.status', Constant::STATUS_ACTIVE)
                ->where('idrole', auth()->guard('api')->user()->idrole)
                ->orderBy('sequence', 'ASC')
                ->get();

        $menus = array();

        foreach ($menusByRole as $index => $menu) {
            $slug = $menu->slug;
            if($slug == '/location-enterprise/'){
                $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;
                $slug = $slug . $identerprise;
            }

            $menus[$index]["idmenu"]        = $menu->idmenu;
            $menus[$index]["name"]          = $menu->name;
            $menus[$index]["slug"]          = $slug;
            $menus[$index]["parent_idmenu"] = $menu->parent_idmenu;
            $menus[$index]["icon"]          = $menu->icon;
            $menus[$index]["static_content_idstatic_content"]    = $menu->static_content_idstatic_content;
            $menus[$index]["submenu"]   = $this->getSubMenu($menu->idmenu, auth()->guard('api')->user()->idrole);
        }

        return Response::success($menus);

    }

    private function getSubMenu($parent, $idrole)
    {
        $subsByParent = RoleAccess::leftJoin('web_menu', 'web_menu.idmenu', '=', 'role_access.idmenu')
            ->where('web_menu.parent_idmenu', $parent)
            ->where('role_access.status', Constant::STATUS_ACTIVE)
            ->where('idrole', $idrole)
            ->orderBy('sequence', 'ASC')
            ->get();

        $subs = array();

        foreach ($subsByParent as $index => $menu) {
            $subs[$index]["idmenu"]        = $menu->idmenu;
            $subs[$index]["name"]          = $menu->name;
            $subs[$index]["slug"]          = $menu->slug;
            $subs[$index]["parent_idmenu"] = $menu->parent_idmenu;
            $subs[$index]["static_content_idstatic_content"]    = $menu->static_content_idstatic_content;
            $subs[$index]["submenu"]   = $this->getSubMenu($menu->idmenu, $idrole);
        }

        return $subs;
    }
}
