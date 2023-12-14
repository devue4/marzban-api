import requests
import json
from datetime import datetime, timezone


class API:

    def __init__(self, base_url,username,password):
        self.base_url = base_url
        self.username = username
        self.password = password
        url = f'{self.base_url}/api/admin/token'

        data = {
            'username': self.username,
            'password': self.password
        }
        response = requests.post(url, data=data,headers={'Content-Type': 'application/x-www-form-urlencoded'})

        if response.status_code == 200:
            self.access_token = response.json()['access_token']
        else:
            print(f"Failed to obtain access token. Status code: {str(response.status_code)+' '+response.text}")
            self.access_token = None
        
    def get_users(self):
        url = f'{self.base_url}/api/users'

        response = self.send_request(url, method='get')
        return response.json()

    def get_user(self,username):
        url = f"{self.base_url}/api/user/{username}"
        response = self.send_request(url)
        return response.json()
    
    def add_user(self, username, traffic=0, day=30):
        url = f'{self.base_url}/api/user'
        data = {
            "username": username,
            "status": "on_hold",
            "data_limit": traffic * 1073741824,
            "data_limit_reset_strategy": "no_reset",
            "proxies": {"vless": ["id"]},
            "inbounds": {"vless": ["VLESS_INBOUND"]},
            "on_hold_expire_duration": day * 86400
        }
        response = self.send_request(url, method='post', data=json.dumps(data))
        return response.json()

    def delete_user(self,username):
        url = f"{self.base_url}/api/user/{username}"
        response = self.send_request(url,method='delete')
        return response.json()
    
    def edit_user(self,username,status='active',traffic=0,day=datetime.now(timezone.utc).strftime("%Y-%m-%d")):
        url = f'{self.base_url}/api/user/{username}'
        def unix_timestamp(input_date):
            target_date = datetime.strptime(input_date, "%Y-%m-%d").replace(tzinfo=timezone.utc)
            epoch = datetime(1970, 1, 1, tzinfo=timezone.utc)
            time_difference = target_date - epoch
            unix_timestamp = int(time_difference.total_seconds())
            return unix_timestamp
        data = {
            "status": status,
            "data_limit": traffic * 1073741824,
            "expire": unix_timestamp(day)
        }
        response = self.send_request(url,"put",data)
        return response.json()
    
    def send_request(self, url, method, data=None):
        headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {self.access_token}'
        }

        try:
            if method.lower() == 'get':
                response = requests.get(url, headers=headers)
            elif method.lower() == 'post':
                response = requests.post(url, headers=headers, data=json.dumps(data))
            elif method.lower() == 'put':
                response = requests.put(url, headers=headers, data=json.dumps(data))
            elif method.lower() == 'delete':
                response = requests.delete(url, headers=headers)
            else:
                print(f"Unsupported HTTP method: {method}")
                return None

            response.raise_for_status()
            return response

        except requests.exceptions.HTTPError as errh:
            print("HTTP Error:", errh)
        except requests.exceptions.ConnectionError as errc:
            print("Error Connecting:", errc)
        except requests.exceptions.Timeout as errt:
            print("Timeout Error:", errt)
        except requests.exceptions.RequestException as err:
            print("Something went wrong:", err)
            return None

api = API("https://{SERVER_ADDRESS}:{POSR}","{USERNAME}","{PASSWORD}")
print(api.add_user("{USERNAME}","{TRAFFIC}","{DAY}"))
print(api.edit_user("{USERNAME}",100,"2024-10-01"))
print(api.delete_user("{USERNAME}"))
print(api.get_user("{USERNAME}"))
print(api.get_users())