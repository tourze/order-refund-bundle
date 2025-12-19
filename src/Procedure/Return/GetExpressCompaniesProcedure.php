<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Return;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Param\Return\GetExpressCompaniesProcedureParam;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;

#[MethodTag(name: '退货管理')]
#[MethodDoc(description: '获取支持的快递公司列表')]
#[MethodExpose(method: 'GetExpressCompaniesProcedure')]
class GetExpressCompaniesProcedure extends BaseProcedure
{
    public function __construct(
        private readonly ExpressCompanyRepository $expressCompanyRepository,
    ) {
    }

    /**
     * @phpstan-param GetExpressCompaniesProcedureParam $param
     */
    public function execute(GetExpressCompaniesProcedureParam|RpcParamInterface $param): ArrayResult
    {
        $companies = $this->expressCompanyRepository->findActiveCompanies();

        $companiesArray = [];
        foreach ($companies as $company) {
            $companiesArray[] = [
                'code' => $company->getCode(),
                'name' => $company->getName(),
                'trackingUrl' => $company->getTrackingUrlTemplate(),
            ];
        }

        return new ArrayResult([
            'companies' => $companiesArray,
            'total' => count($companiesArray),
        ]);
    }
}
