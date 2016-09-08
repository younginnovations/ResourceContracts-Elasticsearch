<?php namespace App\Services;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use League\Route\Http\Exception;

/**
 * Delete Contract's Metadata,Annotations and Text
 * Class DeleteContractService
 * @package App\Services
 */
class DeleteContractService extends Service
{

    /**
     * Delete Contract
     * @param $request
     * @return mixed
     */
    public function deleteContract($request)
    {
        $id                      = $request['id'];
        $response['metadata'][]    = $this->deleteMetadata($id);
        $response['pdftext'][]     = $this->deletePdfText($id);
        $response['annotations'][] = $this->deleteAnnotations($id);
        $response['master'][]      = $this->deleteMaster($id);

        return $response;
    }

    /**
     * Delete Metadata
     * @param $id
     * @return array
     */
    public function deleteMetadata($id)
    {
        try {
            $params['index'] = $this->index;
            $params['type']  = "metadata";
            $params['id']    = $id;

            $delete = $this->deleteDocument($params);
            logger()->info("Metadata Deleted", $delete);

            return $delete;
        } catch (Missing404Exception $e) {
            logger()->error("Metadata not found", [$e->getMessage()]);

            return "Metadata not found";
        }
    }


    /**
     * Delete Pdf TExt
     * @param $id
     * @return array
     */
    public function deletePdfText($id)
    {
        try {
            $params['index']                                = $this->index;
            $params['type']                                 = "pdf_text";
            $params['body']['query']['term']['contract_id'] = $id;

            $delete = $this->deleteDocumentByQuery($params);
            logger()->info("Pdf Text Deleted", $delete);

            return $delete;
        } catch (Missing404Exception $e) {
            logger()->error("Pdf text not found", [$e->getMessage()]);

            return "Text not found";
        }

    }

    /**
     * Delete Annotations
     * @param $id
     * @return array
     */
    public function deleteAnnotations($id)
    {
        try {
            $params['index']                                = $this->index;
            $params['type']                                 = "annotations";
            $params['body']['query']['term']['contract_id'] = $id;

            $delete = $this->deleteDocumentByQuery($params);
            logger()->info("Annotations deleted", $delete);

            return $delete;
        } catch (Missing404Exception $e) {
            logger()->error("Annotaions not found", [$e->getMessage()]);

            return "Annotations not found";
        }
    }

    /**
     * Delete contract from master type
     * @param $id
     * @return array
     */
    public function deleteMaster($id)
    {
        try {
            $params['index'] = $this->index;
            $params['type']  = "master";
            $params['id']    = $id;
            $delete          = $this->deleteDocument($params);
            logger()->info("Master Deleted", $delete);

            return $delete;
        } catch (Missing404Exception $e) {
            logger()->error("Master Not found", [$e->getMessage()]);

            return "Master not found";
        }
    }

    public function deleteExternalSource($source)
    {
        try{
            $response=[];

            $params['index']= $this->index;
            $params['type']="metadata";
            $params['body']=[
                "query"=>[
                    "term"=>[
                        "external_source"=>[
                            "value"=>strtolower($source)
                        ]
                    ]
                ]
            ];

            $results = $this->es->search($params);
            $results = $results['hits']['hits'];
            foreach($results as $result)
            {
                $data['id']=$result['_id'];
                $resp = $this->deleteContract($data);
                $response=array_merge($response,$resp);
            }
            logger()->info(sprintf("Contract deleted with source=%s ",$source), $response);
            return $response;
        }
        catch(Exception $e)
        {
            logger()->error(sprintf("Contract cannot be deleted with source=%s ",$source), [$e->getMessage()]);

            return "Master not found";
        }

    }
}
