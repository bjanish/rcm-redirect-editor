<?php

namespace RcmRedirectEditor\ApiController;

use Rcm\Entity\Redirect;
use Rcm\Exception\RedirectException;
use RcmRedirectEditor\InputFilter\RedirectInputFilter;
use Reliv\RcmApiLib\Controller\AbstractRestfulJsonController;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;
use Zend\Validator\Uri;

/**
 * RedirectController
 *
 * LongDescHere
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   Redirect\ApiController
 * @author    Brian Janish <bjanish@relivinc.com>
 * @copyright 2015 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class RedirectController extends AbstractRestfulJsonController
{
    /**
     * getRedirectRepo
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRedirectRepo()
    {
        $em = $this->getEntityManager();

        $redirectRepo = $em->getRepository(
            '\Rcm\Entity\Redirect'
        );
        return $redirectRepo;
    }
    /**
     * getEntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->serviceLocator->get('Doctrine\ORM\EntityManager');
    }

    /**
     * delete
     *
     * @param mixed $id
     * @return \Reliv\RcmApiLib\Http\ApiResponse
     */
    public function delete($id)
    {
        if (!$this->rcmIsAllowed(
            'sites',
            'admin'
        )
        ) {
            return $this->getApiResponse(
                null,
                401
            );
        }

        $id = (int) $id;

        $redirectRepo = $this->getRedirectRepo();

        $redirectToUpdate = $redirectRepo->findOneBy(
            ['redirectId' => $id]
        );

        if (!$redirectToUpdate) {
            return $this->getApiResponse(
                $redirectToUpdate,
                404
            );
        }
        $em = $this->getEntityManager();
        $em->remove($redirectToUpdate);
        $em->flush();

        return $this->getApiResponse(null);
    }

    /**
     * update
     *
     * @param mixed $id
     * @param mixed $data
     * @return \Reliv\RcmApiLib\Http\ApiResponse
     */
    public function update($id, $data)
    {
        if (!$this->rcmIsAllowed(
            'sites',
            'admin'
        )
        ) {
            return $this->getApiResponse(
                null,
                401
            );
        }

        $id = (int) $id;

        $inputFilter = new RedirectInputFilter();

        $inputFilter->setData($data);

        if (!$inputFilter->isValid()) {
            return $this->getApiResponse(
                null,
                400,
                $inputFilter
            );
        }

        $data = $inputFilter->getValues();

        $redirectRepo = $this->getRedirectRepo();

        $redirectToUpdate = $redirectRepo->find($id);

        if (!$redirectToUpdate) {
            return $this->getApiResponse(
                null,
                404
            );
        }

        $redirectToUpdate->setRedirectUrl($data['redirectUrl']);
        $redirectToUpdate->setRequestUrl($data['requestUrl']);
        $redirectToUpdate->setSiteId($data['siteId']);

        $redirectRepo->save($redirectToUpdate);

//        $em = $this->getEntityManager();
//        $em->flush();

        return $this->getApiResponse(
            $redirectToUpdate
        );

    }

    /**
     * create
     *
     * @param mixed $data
     * @return \Reliv\RcmApiLib\Http\ApiResponse
     */
    public function create($data)
    {
        if (!$this->rcmIsAllowed(
            'sites',
            'admin'
        )
        ) {
            return $this->getApiResponse(
                null,
                401
            );
        }

        $inputFilter = new RedirectInputFilter();

        $inputFilter->setData($data);

        if (!$inputFilter->isValid()) {
            return $this->getApiResponse(
                null,
                400,
                $inputFilter
            );
        }

        $data = $inputFilter->getValues();

        $newRedirect = new Redirect();

        // @TODO filter data
        $newRedirect->populate($data);

        $entityManager = $this->getEntityManager();

        try {
            $redirectRepo = $entityManager->getRepository(
                '\Rcm\Entity\Redirect'
            );
            $redirectRepo->save($newRedirect);

        } catch (RedirectException $e) {
            return $this->getApiResponse(
                null,
                400,
                $e
            );
        }
        return $this->getApiResponse(
            $newRedirect
        );
    }

    /**
     * getList
     *
     * @return mixed|\Zend\Stdlib\ResponseInterface|JsonModel
     */
    public function getList()
    {
        /* ACL */

        if (!$this->rcmIsAllowed(
            'sites',
            'admin'
        )
        ) {
            return $this->getApiResponse(
                null,
                401
            );
        }

        $em = $this->getEntityManager();

        /* ***** filter by default redirects */
        $default = $this->params()->fromQuery('default-redirects');

        if ($default !== null) {
            $default = (bool) $default;
        }

        /* get list of default redirects */
        if ($default === true) {
            $redirectList = $em->getRepository('Rcm\Entity\Redirect')->findBy(
                ["site" => null]
            );

            return $this->getApiResponse($redirectList);
        }

        /* get list of all redirects that are NOT default redirects */
        if ($default === false) {
            $queryBuilder = $em->createQueryBuilder();

            $queryBuilder->select('r')
                ->from('Rcm\Entity\Redirect', 'r')
                ->where('r.siteId IS NOT NULL');

            $redirectList = $queryBuilder->getQuery()->getResult();

            return $this->getApiResponse($redirectList);
        }

        /* filter by siteId */
        $siteId = $this->params()->fromQuery('siteId');

        if ($siteId !== null) {
            $siteId = (int) $siteId;

            $site = $em->getRepository('Rcm\Entity\Site')->find($siteId);

            $redirectList = $em->getRepository('Rcm\Entity\Redirect')->findBy(
                ["site" => $site]
            );

            return $this->getApiResponse($redirectList);
        }

        /* all sites */
        $redirectList = $em->getRepository('Rcm\Entity\Redirect')->findAll();

        return $this->getApiResponse(
            $redirectList
        );
    }
}
