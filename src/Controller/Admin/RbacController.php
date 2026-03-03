<?php

namespace App\Controller\Admin;

use App\Entity\Permission;
use App\Entity\Role;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rbac')]
#[IsGranted('ROLE_ADMIN')]
class RbacController extends AbstractController
{
    #[Route('/', name: 'admin_rbac_index')]
    public function index(RoleRepository $roleRepository, PermissionRepository $permissionRepository): Response
    {
        return $this->render('admin/rbac/index.html.twig', [
            'roles' => $roleRepository->findAll(),
            'permissions' => $permissionRepository->findAll(),
            'groupedPermissions' => $permissionRepository->findAllGroupedByCategory(),
        ]);
    }

    #[Route('/roles', name: 'admin_rbac_roles')]
    public function roles(RoleRepository $roleRepository): Response
    {
        return $this->render('admin/rbac/roles.html.twig', [
            'roles' => $roleRepository->findAll(),
        ]);
    }

    #[Route('/role/new', name: 'admin_rbac_role_new', methods: ['GET', 'POST'])]
    public function newRole(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $role = new Role();
            $role->setName((string) $request->request->get('name'));
            $description = $request->request->get('description');
            if (is_string($description)) {
                $role->setDescription($description);
            }

            $em->persist($role);
            $em->flush();

            $this->addFlash('success', 'Rôle créé avec succès.');
            return $this->redirectToRoute('admin_rbac_roles');
        }

        return $this->render('admin/rbac/role_form.html.twig');
    }

    #[Route('/role/{id}/edit', name: 'admin_rbac_role_edit', methods: ['GET', 'POST'])]
    public function editRole(Role $role, Request $request, EntityManagerInterface $em, PermissionRepository $permissionRepository): Response
    {
        if ($request->isMethod('POST')) {
            $role->setName((string) $request->request->get('name'));
            $description = $request->request->get('description');
            if (is_string($description)) {
                $role->setDescription($description);
            }

            // Update permissions
            $role->getPermissions()->clear();
            $permissionIds = $request->request->all('permissions');
            foreach ($permissionIds as $permissionId) {
                $permission = $permissionRepository->find($permissionId);
                if ($permission !== null) {
                    $role->addPermission($permission);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Rôle mis à jour avec succès.');
            return $this->redirectToRoute('admin_rbac_roles');
        }

        return $this->render('admin/rbac/role_form.html.twig', [
            'role' => $role,
            'groupedPermissions' => $permissionRepository->findAllGroupedByCategory(),
        ]);
    }

    #[Route('/role/{id}/delete', name: 'admin_rbac_role_delete', methods: ['POST'])]
    public function deleteRole(Role $role, EntityManagerInterface $em): Response
    {
        if ($role->getUsers()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce rôle car il est attribué à des utilisateurs.');
            return $this->redirectToRoute('admin_rbac_roles');
        }

        $em->remove($role);
        $em->flush();

        $this->addFlash('success', 'Rôle supprimé avec succès.');
        return $this->redirectToRoute('admin_rbac_roles');
    }

    #[Route('/permissions', name: 'admin_rbac_permissions')]
    public function permissions(PermissionRepository $permissionRepository): Response
    {
        return $this->render('admin/rbac/permissions.html.twig', [
            'groupedPermissions' => $permissionRepository->findAllGroupedByCategory(),
            'categories' => $permissionRepository->findAllCategories(),
        ]);
    }

    #[Route('/permission/new', name: 'admin_rbac_permission_new', methods: ['GET', 'POST'])]
    public function newPermission(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $permission = new Permission();
            $permission->setName((string) $request->request->get('name'));
            $description = $request->request->get('description');
            if (is_string($description)) {
                $permission->setDescription($description);
            }
            $permission->setCategory((string) $request->request->get('category'));

            $em->persist($permission);
            $em->flush();

            $this->addFlash('success', 'Permission créée avec succès.');
            return $this->redirectToRoute('admin_rbac_permissions');
        }

        return $this->render('admin/rbac/permission_form.html.twig');
    }

    #[Route('/permission/{id}/delete', name: 'admin_rbac_permission_delete', methods: ['POST'])]
    public function deletePermission(Permission $permission, EntityManagerInterface $em): Response
    {
        $em->remove($permission);
        $em->flush();

        $this->addFlash('success', 'Permission supprimée avec succès.');
        return $this->redirectToRoute('admin_rbac_permissions');
    }

    #[Route('/initialize', name: 'admin_rbac_initialize')]
    public function initializePermissions(EntityManagerInterface $em, PermissionRepository $permissionRepository, RoleRepository $roleRepository): Response
    {
        // Define default permissions
        $defaultPermissions = [
            'user' => [
                ['name' => 'user_view', 'description' => 'Voir les utilisateurs'],
                ['name' => 'user_create', 'description' => 'Créer des utilisateurs'],
                ['name' => 'user_edit', 'description' => 'Modifier des utilisateurs'],
                ['name' => 'user_delete', 'description' => 'Supprimer des utilisateurs'],
            ],
            'course' => [
                ['name' => 'course_view', 'description' => 'Voir les cours'],
                ['name' => 'course_create', 'description' => 'Créer des cours'],
                ['name' => 'course_edit', 'description' => 'Modifier des cours'],
                ['name' => 'course_delete', 'description' => 'Supprimer des cours'],
            ],
            'role' => [
                ['name' => 'role_view', 'description' => 'Voir les rôles'],
                ['name' => 'role_create', 'description' => 'Créer des rôles'],
                ['name' => 'role_edit', 'description' => 'Modifier des rôles'],
                ['name' => 'role_delete', 'description' => 'Supprimer des rôles'],
            ],
            'order' => [
                ['name' => 'order_view', 'description' => 'Voir les commandes'],
                ['name' => 'order_manage', 'description' => 'Gérer les commandes'],
            ],
            'marketplace' => [
                ['name' => 'product_view', 'description' => 'Voir les produits'],
                ['name' => 'product_create', 'description' => 'Créer des produits'],
                ['name' => 'product_edit', 'description' => 'Modifier des produits'],
                ['name' => 'product_delete', 'description' => 'Supprimer des produits'],
                ['name' => 'job_view', 'description' => 'Voir les emplois'],
                ['name' => 'job_create', 'description' => 'Créer des emplois'],
                ['name' => 'job_edit', 'description' => 'Modifier des emplois'],
                ['name' => 'job_delete', 'description' => 'Supprimer des emplois'],
            ],
        ];

        $createdCount = 0;
        foreach ($defaultPermissions as $category => $permissions) {
            foreach ($permissions as $permData) {
                $existing = $permissionRepository->findOneBy(['name' => $permData['name']]);
                if ($existing === null) {
                    $permission = new Permission();
                    $permission->setName($permData['name']);
                    $permission->setDescription($permData['description']);
                    $permission->setCategory($category);
                    $em->persist($permission);
                    $createdCount++;
                }
            }
        }

        // Assign all permissions to admin role
        $adminRole = $roleRepository->findOneBy(['name' => 'admin']);
        if ($adminRole !== null) {
            $allPermissions = $permissionRepository->findAll();
            foreach ($allPermissions as $permission) {
                if (!$adminRole->hasPermission($permission->getName() ?? '')) {
                    $adminRole->addPermission($permission);
                }
            }
        }

        $em->flush();

        $this->addFlash('success', "$createdCount permissions créées et assignées au rôle admin.");
        return $this->redirectToRoute('admin_rbac_index');
    }
}
